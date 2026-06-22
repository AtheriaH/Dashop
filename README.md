# Dashop 🛒

**The Ultimate GUI-Based Economy Shop for PocketMine-MP 5**

Dashop is a lightweight, highly customizable, and completely GUI-driven shop plugin designed exclusively for PocketMine-MP API 5. It allows server administrators to manage categories, items, and prices through highly organized YAML files, while players enjoy a seamless form-based shopping experience.

Built with SkyBlock, Factions, and Survival servers in mind, Dashop handles massive bulk transactions safely while keeping your server lag-free.

---

## ✨ Key Features

* **100% GUI Driven:** Players interact entirely through clean UI form menus. No messy chat commands to buy or sell.
* **Custom Bulk Transactions:** Players can type exactly how many items they want to buy or sell (up to a 10,000 hard-cap to prevent lag). The plugin automatically splits massive purchases into legal Minecraft stacks (64).
* **Smart YAML Storage:** Dashop reads directly from beautifully organized `.yml` files in the `categories` folder, allowing you to instantly add hundreds of items at once.
* **Inventory & Economy Protection:** Transactions instantly halt if a player's inventory is full, or if they lack the required funds or items.
* **World Restrictions:** Prevent players from accessing `/shop` in specific worlds (like the `hub`, `lobby`, or PvP arenas) via a simple `config.yml`.
* **PM5 String Parser Support:** Fully utilizes the modern PocketMine `StringToItemParser`, ensuring maximum compatibility with PM5 string IDs.

---

## 📦 Requirements

* **PocketMine-MP:** API 5.0.0 or higher
* **EconomyAPI:** Required for all money transactions.

---

## 🛠️ Commands & Permissions

| Command | Alias | Description | Permission | Default |
| :--- | :--- | :--- | :--- | :--- |
| `/dashop` | `/shop` | Opens the main shop menu. | `dashop.command.shop` | `true` |

---

## 🚀 Installation

1. Download the latest compiled `Dashop.phar` from Poggit or GitHub.
2. Drop the `.phar` file into your server's `plugins/` folder.
3. Ensure you have **EconomyAPI** installed.
4. Restart your server.
5. The plugin will automatically generate its `plugin_data/Dashop/` folder.

---

## 📖 Configuration & File Setup

### 1. Main Configuration (`config.yml`)
When you start the server, Dashop generates a `config.yml` file where you can customize the shop title and block specific worlds:

```yaml
---
# The title that appears at the top of the Main Menu
shop_title: "Server Shop"

# Worlds where players cannot open the shop
restricted_worlds:
  - lobby
  - hub
  - pvp_arena
...
