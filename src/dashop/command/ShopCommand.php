<?php

declare(strict_types=1);

namespace dashop\command;

use dashop\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
// This is the crucial line you were missing!
use dashop\form\ShopForm; 

class ShopCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("dashop", "Open the server shop", "/dashop", ["shop"]);
        $this->setPermission("dashop.command.shop");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
            return false;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        $restrictedWorlds = $this->plugin->getConfig()->get("restricted_worlds", []);

        if (in_array($sender->getWorld()->getFolderName(), $restrictedWorlds)) {
            $sender->sendMessage(TextFormat::RED . "You cannot use the shop in this world!");
            return false;
        }

        // If a player types a category like /shop ores
        if (isset($args[0])) {
            $categoryId = strtolower($args[0]);
            $categories = $this->plugin->getShopManager()->getCategories();

            if (isset($categories[$categoryId])) {
                ShopForm::sendItemMenu($sender, $this->plugin, $categoryId);
                return true;
            } else {
                $sender->sendMessage(TextFormat::RED . "The category '$categoryId' does not exist.");
                return false;
            }
        }

        // Opens the main menu safely!
        ShopForm::sendMainMenu($sender, $this->plugin);
        return true;
    }
}
