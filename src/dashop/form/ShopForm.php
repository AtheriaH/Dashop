<?php

declare(strict_types=1);

namespace dashop\form;

use dashop\Main;
use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\StringToItemParser;
use onebone\economyapi\EconomyAPI;

class ShopForm {

    // --- MENU 1: THE CATEGORIES ---
    public static function sendMainMenu(Player $player, Main $plugin): void {
        $categories = $plugin->getShopManager()->getCategories();
        
        if (empty($categories)) {
            $player->sendMessage(TextFormat::RED . "The shop is currently empty!");
            return;
        }

        $form = new class($categories, $plugin) implements Form {
            private array $categoryKeys = [];

            public function __construct(private array $categories, private Main $plugin) {
                foreach ($this->categories as $key => $data) {
                    $this->categoryKeys[] = (string)$key; 
                }
            }

            public function jsonSerialize(): array {
                $buttons = [];
                foreach ($this->categories as $key => $data) {
                    $name = $data["name"] ?? (string)$key;
                    $buttons[] = ["text" => "§l§8" . $name];
                }

                return [
                    "type" => "form",
                    "title" => "§l§9" . $this->plugin->getConfig()->get("shop_title", "Server Shop"),
                    "content" => "§7Select a category to browse:",
                    "buttons" => $buttons
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) return;
                
                $selectedKey = $this->categoryKeys[$data];
                ShopForm::sendItemMenu($player, $this->plugin, $selectedKey);
            }
        };

        $player->sendForm($form);
    }

    // --- MENU 2: THE ITEMS ---
    public static function sendItemMenu(Player $player, Main $plugin, string $categoryKey): void {
        $categories = $plugin->getShopManager()->getCategories();
        $categoryData = $categories[$categoryKey] ?? [];

        $items = [];
        if (isset($categoryData["sub_categories"])) {
            $subCategories = $categoryData["sub_categories"];
            if (isset($subCategories["items"]["items"])) {
                $items = $subCategories["items"]["items"];
            } else {
                foreach ($subCategories as $subCatData) {
                    if (isset($subCatData["items"])) {
                        foreach ($subCatData["items"] as $itemString => $itemData) {
                            $items[(string)$itemString] = $itemData;
                        }
                    }
                }
            }
        }

        if (empty($items)) {
            $player->sendMessage(TextFormat::RED . "There are no items in this category!");
            return;
        }

        $form = new class($items, $plugin, $categoryKey) implements Form {
            private array $itemKeys = [];

            public function __construct(private array $items, private Main $plugin, private string $categoryKey) {
                foreach ($this->items as $key => $data) {
                    $this->itemKeys[] = (string)$key;
                }
            }

            public function jsonSerialize(): array {
                $buttons = [];
                foreach ($this->items as $key => $data) {
                    $name = $data["name"] ?? (string)$key;
                    $buyPrice = isset($data["buy_price"]) ? "$" . $data["buy_price"] : "N/A";
                    $buttons[] = ["text" => $name . "\n§r§8Buy: §2" . $buyPrice];
                }

                return [
                    "type" => "form",
                    "title" => "§l§9" . ucfirst($this->categoryKey),
                    "content" => "§7Select an item to buy or sell:",
                    "buttons" => $buttons
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopForm::sendMainMenu($player, $this->plugin);
                    return;
                }

                $selectedItemKey = $this->itemKeys[$data];
                ShopForm::sendTransactionMenu($player, $this->plugin, $this->categoryKey, $selectedItemKey, $this->items[$selectedItemKey]);
            }
        };

        $player->sendForm($form);
    }

    // --- MENU 3: FREE-TYPE QUANTITY SCREEN ---
    public static function sendTransactionMenu(Player $player, Main $plugin, string $categoryKey, string $itemString, array $itemData): void {
        $form = new class($plugin, $categoryKey, $itemString, $itemData) implements Form {
            public function __construct(private Main $plugin, private string $categoryKey, private string $itemString, private array $itemData) {}

            public function jsonSerialize(): array {
                $name = $this->itemData["name"] ?? $this->itemString;
                $buyPrice = $this->itemData["buy_price"] ?? 0;
                $sellPrice = $this->itemData["sell_price"] ?? 0;

                $content = "§l§a" . $name . "§r\n\n";
                $content .= "§7Buy Price: §a$" . $buyPrice . " §7each\n";
                if ($sellPrice > 0) {
                    $content .= "§7Sell Price: §c$" . $sellPrice . " §7each\n";
                } else {
                    $content .= "§cThis item cannot be sold.\n";
                }

                return [
                    "type" => "custom_form",
                    "title" => "§l§9Buy / Sell",
                    "content" => [
                        [
                            "type" => "label",
                            "text" => $content
                        ],
                        [
                            "type" => "input",
                            "text" => "§7Amount to Buy/Sell (Type a number)",
                            "placeholder" => "e.g., 64, 100, 1000",
                            "default" => "1"
                        ],
                        [
                            "type" => "dropdown",
                            "text" => "§7Action",
                            "options" => ["Buy", "Sell"]
                        ]
                    ]
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopForm::sendItemMenu($player, $this->plugin, $this->categoryKey);
                    return;
                }

                $amountInput = $data[1];
                $action = $data[2] === 0 ? "buy" : "sell";

                if (!is_numeric($amountInput) || (int)$amountInput <= 0) {
                    $player->sendMessage(TextFormat::RED . "Please enter a valid positive number!");
                    return;
                }

                $amount = (int)$amountInput;

                if ($amount > 10000) {
                    $player->sendMessage(TextFormat::RED . "To prevent lag, you can only process up to 10,000 items at a time!");
                    return;
                }

                $buyPrice = (float)($this->itemData["buy_price"] ?? 0);
                $sellPrice = (float)($this->itemData["sell_price"] ?? 0);
                
                $itemParser = StringToItemParser::getInstance();
                $parsedItem = $itemParser->parse($this->itemString);
                
                if ($parsedItem === null) {
                    $player->sendMessage(TextFormat::RED . "Error: The server could not parse the item ID '{$this->itemString}'!");
                    return;
                }
                
                $itemsArray = [];
                $tempAmount = $amount;
                $maxStack = $parsedItem->getMaxStackSize();
                
                while ($tempAmount > 0) {
                    $count = min($tempAmount, $maxStack);
                    $itemsArray[] = (clone $parsedItem)->setCount($count);
                    $tempAmount -= $count;
                }

                if ($action === "buy") {
                    if ($buyPrice <= 0) {
                        $player->sendMessage(TextFormat::RED . "This item cannot be bought.");
                        return;
                    }

                    $totalCost = $buyPrice * $amount;
                    $money = EconomyAPI::getInstance()->myMoney($player);

                    if ($money < $totalCost) {
                        $player->sendMessage(TextFormat::RED . "You do not have enough money. You need §c$" . $totalCost);
                        return;
                    }

                    if (!$player->getInventory()->canAddItem(...$itemsArray)) {
                        $player->sendMessage(TextFormat::RED . "Your inventory is full! You cannot fit $amount of this item.");
                        return;
                    }

                    EconomyAPI::getInstance()->reduceMoney($player, $totalCost);
                    $player->getInventory()->addItem(...$itemsArray);
                    
                    $player->sendMessage(TextFormat::GREEN . "You successfully bought {$amount}x " . ($this->itemData["name"] ?? $this->itemString) . " for $" . $totalCost);
                
                } else {
                    if ($sellPrice <= 0) {
                        $player->sendMessage(TextFormat::RED . "This item cannot be sold.");
                        return;
                    }

                    if (!$player->getInventory()->contains(...$itemsArray)) {
                        $player->sendMessage(TextFormat::RED . "You do not have {$amount}x of this item in your inventory to sell!");
                        return;
                    }

                    $totalReward = $sellPrice * $amount;
                    $player->getInventory()->removeItem(...$itemsArray);
                    EconomyAPI::getInstance()->addMoney($player, $totalReward);
                    $player->sendMessage(TextFormat::GREEN . "You successfully sold {$amount}x " . ($this->itemData["name"] ?? $this->itemString) . " for $" . $totalReward);
                }
            }
        };

        $player->sendForm($form);
    }
}
