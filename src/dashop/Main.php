<?php

declare(strict_types=1);

namespace dashop;

use pocketmine\plugin\PluginBase;
use dashop\command\ShopCommand;
use dashop\command\ShopAdminCommand;
use dashop\manager\ShopManager;

class Main extends PluginBase {

    private static Main $instance;
    private ShopManager $shopManager;

    protected function onLoad(): void {
        self::$instance = $this;
    }

    public function onEnable(): void {
        self::$instance = $this;
        
        $this->shopManager = new ShopManager($this);
        
        // Register standard shop command
        $this->getServer()->getCommandMap()->register("dashop", new ShopCommand($this));
        
        // Register the new Admin command!
        $this->getServer()->getCommandMap()->register("dashop", new ShopAdminCommand($this));
        
        $this->getLogger()->info("§aSuccess! Dashop is fully online.");
    }

    public static function getInstance(): Main {
        return self::$instance;
    }

    public function getShopManager(): ShopManager {
        return $this->shopManager;
    }
}
