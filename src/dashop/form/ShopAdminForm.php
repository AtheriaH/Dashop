<?php

declare(strict_types=1);

namespace dashop\form;

use dashop\Main;
use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShopAdminForm {

    // --- 1. THE MAIN ADMIN MENU ---
    public static function sendMainMenu(Player $player, Main $plugin): void {
        $form = new class($plugin) implements Form {
            public function __construct(private Main $plugin) {}

            public function jsonSerialize(): array {
                return [
                    "type" => "form",
                    "title" => "§l§4Shop Admin Panel",
                    "content" => "§7What would you like to manage?",
                    "buttons" => [
                        ["text" => "§l§8Manage Categories\n§r§7Add or Delete Categories"],
                        ["text" => "§l§8Manage Items\n§r§7Add or Delete Items"]
                    ]
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) return;

                if ($data === 0) {
                    ShopAdminForm::sendCategoryManager($player, $this->plugin);
                } elseif ($data === 1) {
                    ShopAdminForm::sendItemManager($player, $this->plugin);
                }
            }
        };
        $player->sendForm($form);
    }

    // --- 2. CATEGORY LIST & DELETE SCREEN ---
    public static function sendCategoryManager(Player $player, Main $plugin): void {
        $categories = $plugin->getShopManager()->getCategories();

        $form = new class($categories, $plugin) implements Form {
            private array $categoryKeys = [];

            public function __construct(private array $categories, private Main $plugin) {
                $this->categoryKeys[] = "CREATE_NEW"; 
                foreach ($this->categories as $key => $data) {
                    $this->categoryKeys[] = $key;
                }
            }

            public function jsonSerialize(): array {
                $buttons = [
                    ["text" => "§l§2+ Create New Category"]
                ];

                foreach ($this->categories as $key => $data) {
                    $name = $data["name"] ?? $key;
                    $buttons[] = ["text" => "§cDelete: §8" . $name];
                }

                return [
                    "type" => "form",
                    "title" => "§l§4Category Manager",
                    "content" => "§7Create a new category, or click an existing one to DELETE it:",
                    "buttons" => $buttons
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopAdminForm::sendMainMenu($player, $this->plugin);
                    return;
                }

                $selectedKey = $this->categoryKeys[$data];

                if ($selectedKey === "CREATE_NEW") {
                    ShopAdminForm::sendCategoryCreateForm($player, $this->plugin);
                } else {
                    $this->plugin->getShopManager()->deleteCategory($selectedKey);
                    $player->sendMessage(TextFormat::GREEN . "Category '$selectedKey' deleted successfully!");
                }
            }
        };
        $player->sendForm($form);
    }

    // --- 3. CREATE CATEGORY SCREEN ---
    public static function sendCategoryCreateForm(Player $player, Main $plugin): void {
        $form = new class($plugin) implements Form {
            public function __construct(private Main $plugin) {}

            public function jsonSerialize(): array {
                return [
                    "type" => "custom_form",
                    "title" => "§l§2Create Category",
                    "content" => [
                        [
                            "type" => "input",
                            "text" => "§7Internal ID (Used for the filename)\n§cNO spaces or symbols!",
                            "placeholder" => "e.g., blocks, armor, food"
                        ],
                        [
                            "type" => "input",
                            "text" => "§7Display Name (Shown to players on the button)",
                            "placeholder" => "e.g., Building Blocks"
                        ]
                    ]
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopAdminForm::sendCategoryManager($player, $this->plugin);
                    return;
                }

                $id = strtolower(trim($data[0]));
                $name = trim($data[1]);

                if ($id === "" || $name === "") {
                    $player->sendMessage(TextFormat::RED . "You must fill out both fields!");
                    return;
                }
                
                if (preg_match('/[^a-z0-9_]/', $id)) {
                    $player->sendMessage(TextFormat::RED . "The Internal ID can only contain lowercase letters, numbers, and underscores!");
                    return;
                }

                $categoryData = [
                    "name" => $name,
                    "sub_categories" => [
                        "items" => [
                            "items" => [] 
                        ]
                    ]
                ];

                $this->plugin->getShopManager()->saveCategory($id, $categoryData);
                $player->sendMessage(TextFormat::GREEN . "Successfully created the category: $name!");
            }
        };
        $player->sendForm($form);
    }

    // --- 4. ITEM MANAGER (SELECT CATEGORY) ---
    public static function sendItemManager(Player $player, Main $plugin): void {
        $categories = $plugin->getShopManager()->getCategories();

        if (empty($categories)) {
            $player->sendMessage(TextFormat::RED . "You need to create a category first!");
            return;
        }

        $form = new class($categories, $plugin) implements Form {
            private array $categoryKeys = [];

            public function __construct(private array $categories, private Main $plugin) {
                foreach ($this->categories as $key => $data) {
                    $this->categoryKeys[] = $key;
                }
            }

            public function jsonSerialize(): array {
                $buttons = [];
                foreach ($this->categories as $key => $data) {
                    $name = $data["name"] ?? $key;
                    $buttons[] = ["text" => $name];
                }

                return [
                    "type" => "form",
                    "title" => "§l§4Select Category",
                    "content" => "§7Select a category to add or manage items inside it:",
                    "buttons" => $buttons
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopAdminForm::sendMainMenu($player, $this->plugin);
                    return;
                }

              // FIX: Force it to be a string!
                $selectedCategory = (string)$this->categoryKeys[$data];
                ShopAdminForm::sendCategoryItemManager($player, $this->plugin, $selectedCategory);
            }
        };
        $player->sendForm($form);
    }

    // --- 5. CATEGORY ITEM MANAGER (ADD/DELETE ITEMS) ---
    public static function sendCategoryItemManager(Player $player, Main $plugin, string $categoryKey): void {
        $categories = $plugin->getShopManager()->getCategories();
        $categoryData = $categories[$categoryKey] ?? [];
        $items = $categoryData["sub_categories"]["items"]["items"] ?? [];

        $form = new class($items, $plugin, $categoryKey) implements Form {
            private array $itemKeys = [];

            public function __construct(private array $items, private Main $plugin, private string $categoryKey) {
                $this->itemKeys[] = "ADD_NEW";
                foreach ($this->items as $key => $data) {
                    $this->itemKeys[] = $key;
                }
            }

            public function jsonSerialize(): array {
                $buttons = [
                    ["text" => "§l§2+ Add New Item"]
                ];

                foreach ($this->items as $key => $data) {
                    $name = $data["name"] ?? $key;
                    $buttons[] = ["text" => "§cDelete: §8" . $name];
                }

                return [
                    "type" => "form",
                    "title" => "§l§4Items in " . $this->categoryKey,
                    "content" => "§7Add a new item, or click an existing one to DELETE it:",
                    "buttons" => $buttons
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopAdminForm::sendItemManager($player, $this->plugin);
                    return;
                }

                $selectedKey = $this->itemKeys[$data];

                if ($selectedKey === "ADD_NEW") {
                    // Open the new Item Source Selector!
                    ShopAdminForm::sendAddItemSourceSelector($player, $this->plugin, $this->categoryKey);
                } else {
                    $categories = $this->plugin->getShopManager()->getCategories();
                    unset($categories[$this->categoryKey]["sub_categories"]["items"]["items"][$selectedKey]);
                    // FIX: Force it to be a string!
                $selectedKey = (string)$this->itemKeys[$data];                  
                    
                    $this->plugin->getShopManager()->saveCategory($this->categoryKey, $categories[$this->categoryKey]);
                    $player->sendMessage(TextFormat::GREEN . "Successfully deleted the item!");
                }
            }
        };
        $player->sendForm($form);
    }

    // --- 6. ADD ITEM: SOURCE SELECTOR ---
    public static function sendAddItemSourceSelector(Player $player, Main $plugin, string $categoryKey): void {
        $form = new class($plugin, $categoryKey) implements Form {
            public function __construct(private Main $plugin, private string $categoryKey) {}

            public function jsonSerialize(): array {
                return [
                    "type" => "form",
                    "title" => "§l§2Add Item",
                    "content" => "§7How would you like to add the item?",
                    "buttons" => [
                        ["text" => "§l§9Read Item in Hand\n§r§7(Hold the item first!)"],
                        ["text" => "§l§8Type ID Manually\n§r§7(Advanced)"]
                    ]
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopAdminForm::sendCategoryItemManager($player, $this->plugin, $this->categoryKey);
                    return;
                }

                if ($data === 0) {
                    // Automatically read the item in their hand!
                    $item = $player->getInventory()->getItemInHand();
                    if ($item->isNull()) {
                        $player->sendMessage(TextFormat::RED . "You must be holding an item in your hand to use this feature!");
                        return;
                    }
                    
                    // Magically convert "Oak Planks" into "oak_planks"
                    $itemId = strtolower(str_replace(' ', '_', $item->getVanillaName()));
                    $defaultName = $item->getName();
                    
                    ShopAdminForm::sendAddItemPriceForm($player, $this->plugin, $this->categoryKey, $itemId, $defaultName);
                } else {
                    // Manual typing mode
                    ShopAdminForm::sendAddItemPriceForm($player, $this->plugin, $this->categoryKey, "", "");
                }
            }
        };
        $player->sendForm($form);
    }

    // --- 7. ADD ITEM: SET PRICES ---
    public static function sendAddItemPriceForm(Player $player, Main $plugin, string $categoryKey, string $defaultId, string $defaultName): void {
        $form = new class($plugin, $categoryKey, $defaultId, $defaultName) implements Form {
            public function __construct(private Main $plugin, private string $categoryKey, private string $defaultId, private string $defaultName) {}

            public function jsonSerialize(): array {
                return [
                    "type" => "custom_form",
                    "title" => "§l§2Set Item Prices",
                    "content" => [
                        [
                            "type" => "input",
                            "text" => "§7Item String ID",
                            "placeholder" => "e.g., minecraft:dirt",
                            "default" => $this->defaultId
                        ],
                        [
                            "type" => "input",
                            "text" => "§7Custom Display Name",
                            "placeholder" => "e.g., Fresh Dirt",
                            "default" => $this->defaultName
                        ],
                        [
                            "type" => "input",
                            "text" => "§7Buy Price (Numbers only)",
                            "placeholder" => "100"
                        ],
                        [
                            "type" => "input",
                            "text" => "§7Sell Price (Numbers only, 0 to disable selling)",
                            "placeholder" => "50"
                        ]
                    ]
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if ($data === null) {
                    ShopAdminForm::sendAddItemSourceSelector($player, $this->plugin, $this->categoryKey);
                    return;
                }

                $itemId = trim($data[0]);
                $name = trim($data[1]);
                $buyPrice = (float)trim($data[2]);
                $sellPrice = (float)trim($data[3]);

                if ($itemId === "") {
                    $player->sendMessage(TextFormat::RED . "You must provide an Item String ID!");
                    return;
                }

                $categories = $this->plugin->getShopManager()->getCategories();
                $categoryData = $categories[$this->categoryKey];

                if (!isset($categoryData["sub_categories"]["items"]["items"])) {
                    $categoryData["sub_categories"]["items"]["items"] = [];
                }

                $categoryData["sub_categories"]["items"]["items"][$itemId] = [
                    "name" => $name === "" ? $itemId : $name,
                    "buy_price" => $buyPrice,
                    "sell_price" => $sellPrice
                ];

                $this->plugin->getShopManager()->saveCategory($this->categoryKey, $categoryData);
                $player->sendMessage(TextFormat::GREEN . "Successfully added $name to the shop!");
            }
        };
        $player->sendForm($form);
    }
}
