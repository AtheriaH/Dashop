<?php

declare(strict_types=1);

namespace dashop\manager;

use dashop\Main;
use pocketmine\utils\Config;

class ShopManager {

    private array $categories = [];
    private string $categoriesPath;

    public function __construct(private Main $plugin) {
        // Define the new folder path
        $this->categoriesPath = $this->plugin->getDataFolder() . "categories/";
        
        // Create the folder if it doesn't exist
        if (!is_dir($this->categoriesPath)) {
            @mkdir($this->categoriesPath);
        }

        $this->loadShops();
    }

    public function loadShops(): void {
        $this->categories = [];
        
        // Scan the folder for any .yml files and load them
        foreach (glob($this->categoriesPath . "*.yml") as $file) {
            $config = new Config($file, Config::YAML);
            
            // Use the filename (without .yml) as the internal key
            $key = basename($file, ".yml");
            $this->categories[$key] = $config->getAll();
        }
        
        $this->plugin->getLogger()->info("§aSuccessfully loaded " . count($this->categories) . " shop categories from the folder!");
    }

    public function getCategories(): array {
        return $this->categories;
    }

    // --- NEW: SAVE A SPECIFIC CATEGORY FILE ---
    public function saveCategory(string $key, array $data): void {
        $this->categories[$key] = $data;
        $config = new Config($this->categoriesPath . $key . ".yml", Config::YAML);
        $config->setAll($data);
        $config->save();
    }
    
    // --- NEW: DELETE A CATEGORY FILE ---
    public function deleteCategory(string $key): void {
        if (isset($this->categories[$key])) {
            unset($this->categories[$key]);
            $file = $this->categoriesPath . $key . ".yml";
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
