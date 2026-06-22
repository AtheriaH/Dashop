<?php

declare(strict_types=1);

namespace dashop\command;

use dashop\Main;
use dashop\form\ShopForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ShopCommand extends Command {

    public function __construct(private Main $plugin) {
        parent::__construct("dashop", "Open the server shop", "/dashop [category]", ["shop"]);
        $this->setPermission("dashop.command.shop");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command must be executed in-game.");
            return false;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        // --- WORLD RESTRICTION LOGIC ---
        // Add the exact folder names of the worlds where you want to block the shop
        $restrictedWorlds = ["lobby", "hub", "world"];
        $currentWorld = $sender->getWorld()->getFolderName();

        if (in_array($currentWorld, $restrictedWorlds)) {
            $sender->sendMessage(TextFormat::RED . "You cannot use the shop in this world!");
            return false;
        }
        // -------------------------------

        // Check if they typed a specific category (e.g., /shop blocks)
        if (isset($args[0])) {
            $categoryId = strtolower($args[0]);
            $categories = $this->plugin->getShopManager()->getCategories();

            if (isset($categories[$categoryId])) {
                // Instantly open exactly that category!
                ShopForm::sendItemMenu($sender, $this->plugin, $categoryId);
                return true;
            } else {
                $sender->sendMessage(TextFormat::RED . "The category '$categoryId' does not exist.");
                return false;
            }
        }

        // FIX: Changed sendCategoryMenu to sendMainMenu to match ShopForm.php
        ShopForm::sendMainMenu($sender, $this->plugin);
        return true;
    }
}
