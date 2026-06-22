<?php

declare(strict_types=1);

namespace dashop\command;

use dashop\Main;
use dashop\form\ShopAdminForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ShopAdminCommand extends Command{

    private Main $plugin;

    public function __construct(Main $plugin){
        parent::__construct(
            "shopadmin",
            "Open the shop admin panel",
            "/shopadmin",
            ["sadmin"]
        );

        $this->plugin = $plugin;

        // FIXED: must match plugin.yml exactly
        $this->setPermission("dashop.command.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{

        if(!$sender instanceof Player){
            $sender->sendMessage("§cRun this command in-game.");
            return true;
        }

        if(!$this->testPermission($sender)){
            return true;
        }

        ShopAdminForm::sendMainMenu($sender, $this->plugin);
        return true;
    }
}
