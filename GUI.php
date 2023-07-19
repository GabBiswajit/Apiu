<?php

namespace skyisland\menu;

use pocketmine\Server;
use pocketmine\player\Player;

use rank\Ranks;
use skyisland\API;
use skyisland\SkyIsland;
use skyisland\EventHandler;
use skyisland\task\KitTask;
use skyisland\menu\gui\pages\PetManageMenu;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\block\BlockFactory;

use muqsit\invmenu\InvMenu;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\inventory\SimpleInventory;
use pocketmine\data\bedrock\EnchantmentIdMap;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;

class GUI
{
  
  /** @var InvMenu */
  private $DoubleChest;
  
  /** @var InvMenu */
  private $SingleChest;
  
  /** @var String */
  private $Window;
  
  /** @var bool */
  private $ItemsReturned;
  
  /** @var bool */
  private $TradeAccepted;
  
  /** @var array */
  private $Listings;
  
  /** @var Instance */
  private static $instance;
  
  /** @var API */
  public $api;
  
  /** @var SkyIsland */
  private $source;
  
  /** @var array */
  private $playerMoney = [];
  
  /** @var Config */
  public $players;
  
  /** @var Config */
  public $config;
  
  public function __construct(SkyIsland $source)
  {
    self::$instance = $this;
    $this->source = $source;
    $this->api = $source->getInstance()->getAPI();
    $this->config = $source->getInstance()->getConfigFile();
    $this->DoubleChest = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
    $this->SingleChest = InvMenu::create(InvMenu::TYPE_CHEST);
    $this->Window = "";
  }
  
  public static function getInstance(): GUI
  {
    return self::$instance;
  }
  
  public function MainGUI(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 1010 && $itemOutMeta === 0)
        {
          $PlayerInfo = API::getPlayerInfo($player);
          $File = $PlayerInfo->getFile();
          $Menu = $File->getMenu();
          $Array = array(
            "GUI" => "UI",
            "UI" => "GUI"
            );
          $New_Menu = $Array[$Menu];
          $File->setMenu($New_Menu);
          $player->removeCurrentWindow();
        }elseif($itemOutId === 381 && $itemOutMeta === 0)
        {
          $this->TravelMenu($player);
        }elseif($itemOutId === 1014 && $itemOutMeta === 0)
        {
          $this->SettingsMenu($player);
        }elseif($itemOutId === 1009 && $itemOutMeta === 0)
        {
          $this->CraftingMenu($player);
        }elseif($itemOutId === 1015 && $itemOutMeta === 0)
        {
          $this->SkillMenu($player);
        }elseif($itemOutId === 1008 && $itemOutMeta === 0)
        {
          $this->BankMenu($player);
        }elseif($itemOutId === 467 && $itemOutMeta === 0)
        {
          $this->FriendsMenu($player);
        }elseif($itemOutId === 1017 && $itemOutMeta === 0)
        {
          $this->VisitMenu($player);
        }elseif($itemOutId === 1012 && $itemOutMeta === 0)
        {
          $this->PotionBagMenu($player);
        }elseif($itemOutId === 54 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 1013 && $itemOutMeta === 0)
        {
          $this->RecipeMenu($player);
        }elseif($itemOutId === 1011 && $itemOutMeta === 0)
        {
          $this->PetsMenu($player);
        }elseif($itemOutId === 130 && $itemOutMeta === 0)
        {
          $this->source->getScheduler()->scheduleDelayedTask(new ClosureTask(
              function () use ($player) : void {
                $this->EnderChestMenu($player);
                if($this->api->getObjective($player) === "Open-EnderChest")
                {
                  $nextObjective = $this->api->getNextObjective($player);
                  $this->api->setObjective($player, $nextObjective);
                }
              }
            ), 5);
        }elseif($itemOutId === 1005 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $menu->setInventoryCloseListener(
      function(Player $player, $inv): void 
      {
        
      }
    );
    $playerHealth = $player->getHealth();
    $playerDefence = $player->getArmorPoints() * 5;
    $playerMana = $playerDefence/20 * $playerHealth;
    $inv = $menu->getInventory();
    $inv->clearAll();
    /*$inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));*/
    $inv->setItem(13, ItemFactory::getInstance()->get(1010, 0, 1)->setCustomName("§r §aProfile §r\n§r §7 §r\n§r §c❤ Health §r" . $playerHealth . "HP §r\n§r §a❈ Defence §r$playerDefence §r\n§r §b✎ Mana §r$playerMana §r\n§r §7 §r\n§r §eClick To View Your §bSky§3Island §eProfile"));
    /*$inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));*/
    $inv->setItem(19, ItemFactory::getInstance()->get(381, 0, 1)->setCustomName("§r §eFast Travel §r\n§r §7 §r\n§r §7Click To Travel Menu"));
    $inv->setItem(20, ItemFactory::getInstance()->get(1014, 0, 1)->setCustomName("§r §eIsland Settings §r\n§r §7 §r\n§r §7Click To View Settings Menu"));
    $inv->setItem(21, ItemFactory::getInstance()->get(1009, 0, 1)->setCustomName("§r §eCrafting Table §r\n§r §7 §r\n§r §7Click To View WorkBench Menu"));
    $inv->setItem(22, ItemFactory::getInstance()->get(1015, 0, 1)->setCustomName("§r §eSkills §r\n§r §7 §r\n§r §7Click To View Your Skill Stats"));
    $inv->setItem(23, ItemFactory::getInstance()->get(1008, 0, 1)->setCustomName("§r §eBank §r\n§r §7 §r\n§r §7Click To Use Bank"));
    $inv->setItem(24, ItemFactory::getInstance()->get(467, 0, 1)->setCustomName("§r §eFriends §r\n§r §7 §r\n§r §7Click To Manage Your Friends Menu"));
    $inv->setItem(25, ItemFactory::getInstance()->get(1017, 0, 1)->setCustomName("§r §eVisit Island §r\n§r §7 §r\n§r §7Click To Visit Public Islands Menu"));
    /*$inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));*/
    $inv->setItem(29, ItemFactory::getInstance()->get(1012, 0, 1)->setCustomName("§r §ePotion Bag §r\n§r §7 §r\n§r §7Click To Open PotionBag Menu"));
    $inv->setItem(30, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eShop §r\n§r §7 §r\n§r §7Click To Use Shop"));
    $inv->setItem(31, ItemFactory::getInstance()->get(1013, 0, 1)->setCustomName("§r §eRecipes §r\n§r §7 §r\n§r §7Click To View Recipes"));
    $inv->setItem(32, ItemFactory::getInstance()->get(1011, 0, 1)->setCustomName("§r §ePets §r\n§r §7 §r\n§r §7Click To Manage Pets"));
    $inv->setItem(33, ItemFactory::getInstance()->get(130, 0, 1)->setCustomName("§r §eEnder Chest §r\n§r §7 §r\n§r §7Click To View Your Enderchest"));
    /*$inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));*/
    $inv->setItem(49, ItemFactory::getInstance()->get(1005, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    /*$inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));*/
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function TravelMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 2 && $itemOutMeta === 0)
        {
          if($player->getLocation()->world->getFolderName() !== $this->source->getInstance()->getPlayerFile($playerName)->get("Island"))
          {
            $this->api->teleportToIsland($player, 1);
          }
        }elseif($itemOutId === 355 && $itemOutMeta === 0)
        {
          if($this->api->fastTravel($player, "Hub"))
          {
            $player->sendMessage("§ateleported to Hub");
          }else{
            $player->sendMessage("§ccan't teleport to Hub");
          }
        }elseif($itemOutId === 15 && $itemOutMeta === 0)
        {
          if($this->api->getLevel($player, "Miner") >= 2)
          {
            if($this->api->fastTravel($player, "Mine"))
            {
              $player->sendMessage("§ateleported to Mine");
            }else{
              $player->sendMessage("§ccan't teleport to Mine");
            }
          }else{
            $player->sendMessage("§cyou need miner level 2 to use this");
          }
        }elseif($itemOutId === 54 && $itemOutMeta === 0)
        {
          if($this->api->fastTravel($player, "Shop"))
          {
            $player->sendMessage("§ateleported to Shop");
          }else{
            $player->sendMessage("§ccan't teleport to Shop");
          }
        }elseif($itemOutId === 17 && $itemOutMeta === 0)
        {
          if($this->api->getLevel($player, "Lumberjack") >= 2)
          {
            if($this->api->fastTravel($player, "Forest"))
            {
              $player->sendMessage("§ateleported to Forest");
            }else{
              $player->sendMessage("§ccan't teleport to Forest");
            }
          }else{
            $player->sendMessage("§cyou need lumberjack level 2 to use this");
          }
        }elseif($itemOutId === 346 && $itemOutMeta === 0)
        {
          if($this->api->fastTravel($player, "Pond"))
          {
            $player->sendMessage("§ateleported to Pond");
          }else{
            $player->sendMessage("§ccan't teleport to Pond");
          }
        }elseif($itemOutId === 296 && $itemOutMeta === 0)
        {
          if($this->api->getLevel($player, "Farmer") >= 2)
          {
            if($this->api->fastTravel($player, "Farm"))
            {
              $player->sendMessage("§ateleported to Farm");
            }else{
              $player->sendMessage("§ccan't teleport to ");
            }
          }else{
            $player->sendMessage("§cyou need farmer level 2 to use this");
          }
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(355, 0, 1)->setCustomName("§r §eHub §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(15, 0, 1)->setCustomName("§r §eMine §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(2, 0, 1)->setCustomName("§r §bSky§3Island §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eShop §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r §eForest §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(346, 0, 1)->setCustomName("§r §ePond §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(296, 0, 1)->setCustomName("§r §eFarm §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function SettingsMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 159 && $itemOutMeta === 13)
        {
          $maxVisitors = $this->api->getMaxVisitors($player);
          if($maxVisitors < 10)
          {
            if($this->api->setMaxVisitors($player, $maxVisitors + 1))
            {
              $inv->setItem(19, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §aMax Visitors§7: §r\n§r §e".$maxVisitors + 1));
              $player->sendMessage("§aSuccessfully updated max visitors to §e".$maxVisitors + 1);
            }else{
              $player->sendMessage("§cError can't update max visitors");
            }
          }
        }elseif($itemOutId === 159 && $itemOutMeta === 14)
        {
          $maxVisitors = $this->api->getMaxVisitors($player);
          if($maxVisitors > 1)
          {
            if($this->api->setMaxVisitors($player, $maxVisitors - 1))
            {
              $inv->setItem(19, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §aMax Visitors§7: §r\n§r §e".$maxVisitors - 1));
              $player->sendMessage("§aSuccessfully updated max visitors to §e".$maxVisitors - 1);
            }else{
              $player->sendMessage("§cError can't update max visitors");
            }
          }
        }elseif($itemOutId === 330 && $itemOutMeta === 0)
        {
          if($this->api->unlockIsland($player))
          {
            $inv->setItem(16, ItemFactory::getInstance()->get(324, 0, 1)->setCustomName("§r §aUnlocked §r\n§r §7Click To Lock The Island §r"));
            $player->sendMessage("§aSuccessfully unlocked your island");
          }else{
            $player->sendMessage("§cError can't unlock your island");
          }
        }elseif($itemOutId === 324 && $itemOutMeta === 0)
        {
          if($this->api->lockIsland($player))
          {
            $inv->setItem(16, ItemFactory::getInstance()->get(330, 0, 1)->setCustomName("§r §cLocked §r\n§r §7Click To Unlock The Island §r"));
            $player->sendMessage("§aSuccessfully locked your island");
          }else{
            $player->sendMessage("§cError can't lock your island");
          }
        }elseif($itemOutId === 2 && $itemOutMeta === 0)
        {
          if($this->api->setIslandSpawn($player))
          {
            $player->sendMessage("§aSuccessfully changed spawn of the island");
          }else{
            $player->sendMessage("§cError can't change spawn of the island");
          }
        }elseif($itemOutId === 288 && $itemOutMeta === 0)
        {
          $canDrop = $this->api->getCanDropItems($player);
          if($canDrop)
          {
            $updateCanDrop = false;
          }else{
            $updateCanDrop = true;
          }
          if($this->api->setCanDropItems($player, $updateCanDrop))
          {
            if($updateCanDrop)
            {
              $player->sendMessage("§aVisiotrs dropping/picking items enabled");
              $inv->setItem(14, ItemFactory::getInstance()->get(288, 0, 1)->setCustomName("§r §aDrop/Pickup Items§7: §aEnabled §r\n§r §7Whether Visitors Can Drop/Pickup Item §r"));
            }else{
              $player->sendMessage("§aVisiotrs dropping/picking items disabled");
              $inv->setItem(14, ItemFactory::getInstance()->get(288, 0, 1)->setCustomName("§r §aDrop/Pickup Items§7: §cDisabled §r\n§r §7Whether Visitors Can Drop/Pickup Item §r"));
            }
          }else{
            $player->sendMessage("§cError can't Update Droping Items");
          }
        }elseif($itemOutId === 450 && $itemOutMeta === 0)
        {
          $canFriendsVisit = $this->api->getFriendsVisit($player);
          if($canFriendsVisit)
          {
            $updatedFriendsVisit = false;
          }else{
            $updatedFriendsVisit = true;
          }
          if($this->api->setFriendsVisit($player, $updatedFriendsVisit))
          {
            if($updatedFriendsVisit)
            {
              $player->sendMessage("§aYour friends can now visit in Lock Mode");
              $inv->setItem(34, ItemFactory::getInstance()->get(450, 0, 1)->setCustomName("§r §aFriends Visit§7: §aEnabled  §r\n§r §7Whether Friends Can Visit If The Island Locked §r"));
            }else{
              $player->sendMessage("§aYour friends can't visit in Lock Mode");
              $inv->setItem(34, ItemFactory::getInstance()->get(450, 0, 1)->setCustomName("§r §aFriends Visit§7: §cDisabled  §r\n§r §7Whether Friends Can Visit If The Island Locked §r"));
            }
          }else{
            $player->sendMessage("§cError can't update Friends Lock");
          }
        }elseif($itemOutId === 397 && $itemOutMeta === 1)
        {
          if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner")
          {
            $player->removeCurrentWindow();
            $this->api->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
              function() use($player, $inv): void
              {
                $this->api->getSource()->getUI()->addMemberMenu($player);
              }
            ), 10);
          }
        }elseif($itemOutId === 278 && $itemOutMeta === 0)
        {
          if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner")
          {
            $this->ManageMembersMenu($player);
          }
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    if($this->api->isLocked($player))
    {
      $lockItem = 330;
      $lockName = "§cLocked §r\n§r §7Click To Unlock The Island";
    }else{
      $lockItem = 324;
      $lockName = "§aUnlocked §r\n§r §7Click To Lock The Island";
    }
    if($this->api->getCanDropItems($player))
    {
      $canDrop = "§aEnabled";
    }else{
      $canDrop = "§cDisabled";
    }
    if($this->api->getFriendsVisit($player))
    {
      $friendsVisit = "§aEnabled";
    }else{
      $friendsVisit = "§cDisabled";
    }
    $inv = $menu->getInventory();
    $maxVisitors = $this->api->getMaxVisitors($player);
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(159, 13, 1)->setCustomName("§r §a+ §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(2, 0, 1)->setCustomName("§r §aSet Spawn §r\n§r §7Sets Island Spawn Point §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(288, 0, 1)->setCustomName("§r §aDrop/Pickup Items§7: $canDrop §r\n§r §7Whether Visitors Can Drop/Pickup Item §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get($lockItem, 0, 1)->setCustomName("§r $lockName §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §aMax Visitors§7: §r\n§r §e$maxVisitors §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(159, 14, 1)->setCustomName("§r §c- §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eManage Members §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(397, 1, 1)->setCustomName("§r §eAdd Member §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(450, 0, 1)->setCustomName("§r §aFriends Visit§7: $friendsVisit  §r\n§r §7Whether Friends Can Visit If The Island Locked §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function CraftingMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 160 && $itemOutMeta === 3)
        {
          return $transaction->discard();
        }elseif($itemOutId === 160 && $itemOutMeta === 14)
        {
          return $transaction->discard();
        }elseif($itemOutId === -161 && $itemOutMeta === 0)
        {
          return $transaction->discard();
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $itemA = $inv->getItem(11);
          $itemB = $inv->getItem(12);
          $itemC = $inv->getItem(13);
          $itemD = $inv->getItem(20);
          $itemE = $inv->getItem(21);
          $itemF = $inv->getItem(22);
          $itemG = $inv->getItem(29);
          $itemH = $inv->getItem(30);
          $itemI = $inv->getItem(31);
          if($itemA->getId() !== 0 && $player->getInventory()->canAddItem($itemA))
          {
            $player->getInventory()->addItem($itemA);
          }elseif($itemA->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemA);
          }
          if($itemB->getId() !== 0 && $player->getInventory()->canAddItem($itemB))
          {
            $player->getInventory()->addItem($itemB);
          }elseif($itemB->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemB);
          }
          if($itemC->getId() !== 0 && $player->getInventory()->canAddItem($itemC))
          {
            $player->getInventory()->addItem($itemC);
          }elseif($itemC->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemC);
          }
          if($itemD->getId() !== 0 && $player->getInventory()->canAddItem($itemD))
          {
            $player->getInventory()->addItem($itemD);
          }elseif($itemD->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemD);
          }
          if($itemE->getId() !== 0 && $player->getInventory()->canAddItem($itemE))
          {
            $player->getInventory()->addItem($itemE);
          }elseif($itemE->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemE);
          }
          if($itemF->getId() !== 0 && $player->getInventory()->canAddItem($itemF))
          {
            $player->getInventory()->addItem($itemF);
          }elseif($itemF->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemF);
          }
          if($itemG->getId() !== 0 && $player->getInventory()->canAddItem($itemG))
          {
            $player->getInventory()->addItem($itemG);
          }elseif($itemG->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemG);
          }
          if($itemH->getId() !== 0 && $player->getInventory()->canAddItem($itemH))
          {
            $player->getInventory()->addItem($itemH);
          }elseif($itemH->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemH);
          }
          if($itemI->getId() !== 0 && $player->getInventory()->canAddItem($itemI))
          {
            $player->getInventory()->addItem($itemI);
          }elseif($itemI->getId() !== 0)
          {
            $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemI);
          }
          $this->MainGUI($player);
          return $transaction->discard();
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
          return $transaction->discard();
        }
        
        $this->source->getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
          function () use ($menu, $player): void
          {
            $RecipeFound = false;
            $inv = $menu->getInventory();
            foreach($this->api->getAllRecipes() as $recipeFile)
            {
              if(!is_dir($this->source->getInstance()->getDataFolder() . "recipes/" . $recipeFile))
              {
                $file = $this->source->getInstance()->getRecipeFile(str_replace(".yml", "", $recipeFile));
                $key = $file->get("Recipe");
                $A = $key[0];
                $B = $key[1];
                $C = $key[2];
                $D = $key[3];
                $E = $key[4];
                $F = $key[5];
                $G = $key[6];
                $H = $key[7];
                $I = $key[8];
                $R = $key[9];
                if(((int)$A[0]) === $inv->getItem(11)->getId() && ((int)$A[2]) <= $inv->getItem(11)->getCount())
                {
                  if(array_key_exists(4, $A) || (((int)$A[1]) === $inv->getItem(11)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $A[3]) === $inv->getItem(11)->getName()))
                  {
                    if(((int)$B[0]) === $inv->getItem(12)->getId() && ((int)$B[2]) <= $inv->getItem(12)->getCount())
                    {
                      if(array_key_exists(4, $B) || (((int)$B[1]) === $inv->getItem(12)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $B[3]) === $inv->getItem(12)->getName()))
                      {
                        if(((int)$C[0]) === $inv->getItem(13)->getId() && ((int)$C[2]) <= $inv->getItem(13)->getCount())
                        {
                          if(array_key_exists(4, $C) || (((int)$C[1]) === $inv->getItem(13)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $C[3]) === $inv->getItem(13)->getName()))
                          {
                            if(((int)$D[0]) === $inv->getItem(20)->getId() && ((int)$D[2]) <= $inv->getItem(20)->getCount())
                            {
                              if(array_key_exists(4, $D) || (((int)$D[1]) === $inv->getItem(20)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $D[3]) === $inv->getItem(20)->getName()))
                              {
                                if(((int)$E[0]) === $inv->getItem(21)->getId() && ((int)$E[2]) <= $inv->getItem(21)->getCount())
                                {
                                  if(array_key_exists(4, $E) || (((int)$E[1]) === $inv->getItem(21)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $E[3]) === $inv->getItem(21)->getName()))
                                  {
                                    if(((int)$F[0]) === $inv->getItem(22)->getId() && ((int)$F[2]) <= $inv->getItem(22)->getCount())
                                    {
                                      if(array_key_exists(4, $F) || (((int)$F[1]) === $inv->getItem(22)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $F[3]) === $inv->getItem(22)->getName()))
                                      {
                                        if(((int)$G[0]) === $inv->getItem(29)->getId() && ((int)$G[2]) <= $inv->getItem(29)->getCount())
                                        {
                                          if(array_key_exists(4, $G) || (((int)$G[1]) === $inv->getItem(29)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $G[3]) === $inv->getItem(29)->getName()))
                                          {
                                            if(((int)$H[0]) === $inv->getItem(30)->getId() && ((int)$H[2]) <= $inv->getItem(30)->getCount())
                                            {
                                              if(array_key_exists(4, $H) || (((int)$H[1]) === $inv->getItem(30)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $H[3]) === $inv->getItem(30)->getName()))
                                              {
                                                if(((int)$I[0]) === $inv->getItem(31)->getId() && ((int)$I[2]) <= $inv->getItem(31)->getCount())
                                                {
                                                  if(array_key_exists(4, $I) || (((int) $I[1]) === $inv->getItem(31)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $I[3]) === $inv->getItem(31)->getName()))
                                                  {
                                                    if(array_key_exists(4, $R))
                                                    {
                                                      if($R[4] === "null")
                                                      {
                                                        if(array_key_exists(5, $R))
                                                        {
                                                          $b_item = ItemFactory::getInstance()->get((int)$R[0], (int)$R[1], (int)$R[2]);
                                                          $b_item->setNamedTag(unserialize($R[5]));
                                                          $b_item->setCustomName(str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $R[3]));
                                                          $inv->setItem(24, $b_item);
                                                        }else{
                                                          $inv->setName(24, ItemFactory::getInstance()->get((int)$R[0], (int)$R[1], (int)$R[2])->setCustomName(str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $R[3])));
                                                        }
                                                      }elseif(count($R[4]) === 2)
                                                      {
                                                        if($this->api->getLevel($player, ((string) $R[4][1])) >= ((int) $R[4][0]))
                                                        {
                                                          if(array_key_exists(5, $R))
                                                          {
                                                            $b_item = ItemFactory::getInstance()->get((int)$R[0], (int)$R[1], (int)$R[2]);
                                                            $b_item->setNamedTag(unserialize($R[5]));
                                                            $b_item->setCustomName(str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $R[3]));
                                                            $inv->setItem(24, $b_item);
                                                          }else{
                                                            $inv->setItem(24, ItemFactory::getInstance()->get((int)$R[0], (int)$R[1], (int)$R[2])->setCustomName(str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $R[3])));
                                                          }
                                                        }
                                                      }elseif(count($R[4]) === 3)
                                                      {
                                                        $color = new Color((int) $R[4][0], (int) $R[4][1], (int) $R[4][2]);
                                                        if(array_key_exists(5, $R))
                                                        {
                                                          $b_item = ItemFactory::getInstance()->get((int)$R[0], (int)$R[1], (int)$R[2])->setCustomColor($color);
                                                          $b_item->setNamedTag(unserialize($R[5]));
                                                          $b_item->setCustomName(str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $R[3]));
                                                          $inv->setItem(24, $b_item);
                                                        }else{
                                                          $inv->setItem(24, ItemFactory::getInstance()->get((int)$R[0], (int)$R[1], (int)$R[2])->setCustomName(str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $R[3]))->setCustomColor($color));
                                                        }
                                                      }
                                                    }else{
                                                       $inv->setItem(24, ItemFactory::getInstance()->get((int)$R[0], (int)$R[1], (int)$R[2])->setCustomName(str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $R[3])));
                                                    }
                                                    $RecipeFound = true;
                                                  }
                                                  break;
                                                }else{
                                                  $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                                }
                                              }else{
                                                $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                              }
                                            }else{
                                              $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                            }
                                          }else{
                                            $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                          }
                                        }else{
                                          $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                        }
                                      }else{
                                        $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                      }
                                    }else{
                                      $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                    }
                                  }else{
                                    $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                  }
                                }else{
                                  $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                                }
                              }else{
                                $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                              }
                            }else{
                              $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                            }
                          }else{
                            $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                          }
                        }else{
                          $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                        }
                      }else{
                        $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                      }
                    }else{
                      $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                    }
                  }else{
                    $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                  }
                }else{
                  $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                }
              }
            }
            if(!$RecipeFound)
            {
              $array = array("0" => $inv->getItem(11), "1" => $inv->getItem(12), "2" => $inv->getItem(13), "3" => $inv->getItem(20), "4" => $inv->getItem(21), "5" => $inv->getItem(22), "6" => $inv->getItem(29), "7" => $inv->getItem(30), "8" => $inv->getItem(31));
              $canCraft = true;
              foreach($array as $key => $item)
              {
                if($item->getCustomName() !== "§r {$item->getVanillaName()} §r\n§r {$this->api->getRarity($item)} §r" && $item->getId() !== 0)
                {
                  $canCraft = false;
                }
              }
              if($canCraft)
              {
                $recipe = $this->api->matchRecipe($array);
                if(!is_null($recipe))
                {
                  $item = $recipe->getResults()[0];
                  $item->setCustomName("§r {$item->getVanillaName()} §r\n§r {$this->api->getRarity($item)} §r");
                  $inv->setItem(24, $item);
                }else{
                  $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
                }
              }
            }
          }
        ), 1);
        
        if($transaction->getAction()->getSlot() === 24)
        {
          $recipe = "";
          foreach($this->api->getAllRecipes() as $recipeFile)
          {
            if(!is_dir($this->source->getInstance()->getDataFolder() . "recipes/" . $recipeFile))
            {
            $file = $this->source->getInstance()->getRecipeFile(str_replace(".yml", "", $recipeFile));
            $key = $file->get("Recipe");
            $A = $key[0];
            $B = $key[1];
            $C = $key[2];
            $D = $key[3];
            $E = $key[4];
            $F = $key[5];
            $G = $key[6];
            $H = $key[7];
            $I = $key[8];
            $inv = $menu->getInventory();
            if(((int)$A[0]) === $inv->getItem(11)->getId() && ((int)$A[2]) <= $inv->getItem(11)->getCount())
            {
              if(array_key_exists(4, $A) || (((int)$A[1]) === $inv->getItem(11)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $A[3]) === $inv->getItem(11)->getName()))
              {
                if(((int)$B[0]) === $inv->getItem(12)->getId() && ((int)$B[2]) <= $inv->getItem(12)->getCount())
                {
                  if(array_key_exists(4, $B) || (((int)$B[1]) === $inv->getItem(12)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $B[3]) === $inv->getItem(12)->getName()))
                  {
                    if(((int)$C[0]) === $inv->getItem(13)->getId() && ((int)$C[2]) <= $inv->getItem(13)->getCount())
                    {
                      if(array_key_exists(4, $C) || (((int)$C[1]) === $inv->getItem(13)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $C[3]) === $inv->getItem(13)->getName()))
                      {
                        if(((int)$D[0]) === $inv->getItem(20)->getId() && ((int)$D[2]) <= $inv->getItem(20)->getCount())
                        {
                          if(array_key_exists(4, $D) || (((int)$D[1]) === $inv->getItem(20)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $D[3]) === $inv->getItem(20)->getName()))
                          {
                            if(((int)$E[0]) === $inv->getItem(21)->getId() && ((int)$E[2]) <= $inv->getItem(21)->getCount())
                            {
                              if(array_key_exists(4, $E) || (((int)$E[1]) === $inv->getItem(21)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $E[3]) === $inv->getItem(21)->getName()))
                              {
                                if(((int)$F[0]) === $inv->getItem(22)->getId() && ((int)$F[2]) <= $inv->getItem(22)->getCount())
                                {
                                  if(array_key_exists(4, $F) || (((int)$F[1]) === $inv->getItem(22)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $F[3]) === $inv->getItem(22)->getName()))
                                  {
                                    if(((int)$G[0]) === $inv->getItem(29)->getId() && ((int)$G[2]) <= $inv->getItem(29)->getCount())
                                    {
                                      if(array_key_exists(4, $G) || (((int)$G[1]) === $inv->getItem(29)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $G[3]) === $inv->getItem(29)->getName()))
                                      {
                                        if(((int)$H[0]) === $inv->getItem(30)->getId() && ((int)$H[2]) <= $inv->getItem(30)->getCount())
                                        {
                                          if(array_key_exists(4, $H) || (((int)$H[1]) === $inv->getItem(30)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $H[3]) === $inv->getItem(30)->getName()))
                                          {
                                            if(((int)$I[0]) === $inv->getItem(31)->getId() && ((int)$I[2]) <= $inv->getItem(31)->getCount())
                                            {
                                              if(array_key_exists(4, $I) || (((int) $I[1]) === $inv->getItem(31)->getMeta() && str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $I[3]) === $inv->getItem(31)->getName()))
                                              {
                                                $recipe = $key;
                                              }
                                            }
                                          }
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
            }
          }
          $type = null;
          if(is_string($recipe))
          {
            $array = array("0" => $inv->getItem(11), "1" => $inv->getItem(12), "2" => $inv->getItem(13), "3" => $inv->getItem(20), "4" => $inv->getItem(21), "5" => $inv->getItem(22), "6" => $inv->getItem(29), "7" => $inv->getItem(30), "8" => $inv->getItem(31));
            $r = $this->api->matchRecipe($array);
            if(!is_null($r))
            {
              $canCraft = true;
              foreach($array as $key => $item)
              {
                if($item->getName() !== "§r {$item->getVanillaName()} §r\n§r {$this->api->getRarity($item)} §r" && $item->getId() !== 0)
                {
                  $canCraft = false;
                }
              }
              if($canCraft)
              {
                if($r instanceof ShapedRecipe)
                {
                  $type = "Shaped";
                }elseif($r instanceof ShapelessRecipe)
                {
                  $type = "Shapeless";
                }
                $recipe = $this->api->getIngredients($array, $r);
              }
            }
          }
          if(!is_string($recipe))
          {
            if(is_null($type))
            {
              $A = $recipe[0];
              $B = $recipe[1];
              $C = $recipe[2];
              $D = $recipe[3];
              $E = $recipe[4];
              $F = $recipe[5];
              $G = $recipe[6];
              $H = $recipe[7];
              $I = $recipe[8];
              $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
              if($player->getInventory()->canAddItem($itemOut))
              {
                $inv->setItem(11, $inv->getItem(11)->setCount($inv->getItem(11)->getCount() - $A[2]));
                $inv->setItem(12, $inv->getItem(12)->setCount($inv->getItem(12)->getCount() - $B[2]));
                $inv->setItem(13, $inv->getItem(13)->setCount($inv->getItem(13)->getCount() - $C[2]));
                $inv->setItem(20, $inv->getItem(20)->setCount($inv->getItem(20)->getCount() - $D[2]));
                $inv->setItem(21, $inv->getItem(21)->setCount($inv->getItem(21)->getCount() - $E[2]));
                $inv->setItem(22, $inv->getItem(22)->setCount($inv->getItem(22)->getCount() - $F[2]));
                $inv->setItem(29, $inv->getItem(29)->setCount($inv->getItem(29)->getCount() - $G[2]));
                $inv->setItem(30, $inv->getItem(30)->setCount($inv->getItem(30)->getCount() - $H[2]));
                $inv->setItem(31, $inv->getItem(31)->setCount($inv->getItem(31)->getCount() - $I[2]));
                $player->getInventory()->addItem($itemOut);
              }
            }elseif($type === "Shaped")
            {
              $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
              if($player->getInventory()->canAddItem($itemOut))
              {
                foreach($recipe as $key => $info)
                {
                  $i = (int) $key;
                  if($i < 3)
                  {
                    $slot = $i + 11;
                    if($inv->getItem($slot)->getName() !== "§r §7 §r")
                    {
                      $inv->setItem($slot, $inv->getItem($slot)->setCount($inv->getItem($slot)->getCount() - $info[2]));
                    }
                  }
                  if($i < 6)
                  {
                    $slot = $i + 17;
                    if($inv->getItem($slot)->getName() !== "§r §7 §r")
                    {
                      $inv->setItem($slot, $inv->getItem($slot)->setCount($inv->getItem($slot)->getCount() - $info[2]));
                    }
                  }
                  if($i < 9)
                  {
                    $slot = $i + 23;
                    if($inv->getItem($slot)->getName() !== "§r §7 §r")
                    {
                      $inv->setItem($slot, $inv->getItem($slot)->setCount($inv->getItem($slot)->getCount() - $info[2]));
                    }
                  }
                }
                $player->getInventory()->addItem($itemOut);
                if($this->api->getObjective($player) === "Craft-WorkBench")
                {
                  if($itemOut->getId() === 58)
                  {
                    $nextObjective = $this->api->getNextObjective($player);
                    $this->api->setObjective($player, $nextObjective);
                  }
                }
                if($this->api->getObjective($player) === "Craft-Pickaxe")
                {
                  if($itemOut->getId() === 270)
                  {
                    $nextObjective = $this->api->getNextObjective($player);
                    $this->api->setObjective($player, $nextObjective);
                  }
                }
              }
            }elseif($type === "Shapeless")
            {
              $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
              if($player->getInventory()->canAddItem($itemOut))
              {
                foreach($recipe as $item)
                {
                  $this->api->removeItem($inv, false, $item);
                }
                $player->getInventory()->addItem($itemOut);
              }
            }
          }
          return $transaction->discard();
        }
        
        return $transaction->continue();
      }
    );
    $menu->setInventoryCloseListener(
      function(Player $player, $inv): void 
      {
        $itemA = $inv->getItem(11);
        $itemB = $inv->getItem(12);
        $itemC = $inv->getItem(13);
        $itemD = $inv->getItem(20);
        $itemE = $inv->getItem(21);
        $itemF = $inv->getItem(22);
        $itemG = $inv->getItem(29);
        $itemH = $inv->getItem(30);
        $itemI = $inv->getItem(31);
        if($itemA->getId() !== 0 && $player->getInventory()->canAddItem($itemA))
        {
          $player->getInventory()->addItem($itemA);
        }elseif($itemA->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemA);
        }
        if($itemB->getId() !== 0 && $player->getInventory()->canAddItem($itemB))
        {
          $player->getInventory()->addItem($itemB);
        }elseif($itemB->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemB);
        }
        if($itemC->getId() !== 0 && $player->getInventory()->canAddItem($itemC))
        {
          $player->getInventory()->addItem($itemC);
        }elseif($itemC->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemC);
        }
        if($itemD->getId() !== 0 && $player->getInventory()->canAddItem($itemD))
        {
          $player->getInventory()->addItem($itemD);
        }elseif($itemD->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemD);
        }
        if($itemE->getId() !== 0 && $player->getInventory()->canAddItem($itemE))
        {
          $player->getInventory()->addItem($itemE);
        }elseif($itemE->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemE);
        }
        if($itemF->getId() !== 0 && $player->getInventory()->canAddItem($itemF))
        {
          $player->getInventory()->addItem($itemF);
        }elseif($itemF->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemF);
        }
        if($itemG->getId() !== 0 && $player->getInventory()->canAddItem($itemG))
        {
          $player->getInventory()->addItem($itemG);
        }elseif($itemG->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemG);
        }
        if($itemH->getId() !== 0 && $player->getInventory()->canAddItem($itemH))
        {
          $player->getInventory()->addItem($itemH);
        }elseif($itemH->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemH);
        }
        if($itemI->getId() !== 0 && $player->getInventory()->canAddItem($itemI))
        {
          $player->getInventory()->addItem($itemI);
        }elseif($itemI->getId() !== 0)
        {
          $player->getWorld()->dropItem(new Vector3(floor($player->getLocation()->x), floor($player->getLocation()->y), floor($player->getLocation()->z)), $itemI);
        }
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(-161, 0, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function createRecipe(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $Recipe = null;
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $A = $inv->getItem(11);
            $B = $inv->getItem(12);
            $C = $inv->getItem(13);
            $D = $inv->getItem(20);
            $E = $inv->getItem(21);
            $F = $inv->getItem(22);
            $G = $inv->getItem(29);
            $H = $inv->getItem(30);
            $I = $inv->getItem(31);
            $R = $inv->getItem(24);
            $AData = [$A->getId(), $A->getMeta(), $A->getCount(), $A->getName()];
            $BData = [$B->getId(), $B->getMeta(), $B->getCount(), $B->getName()];
            $CData = [$C->getId(), $C->getMeta(), $C->getCount(), $C->getName()];
            $DData = [$D->getId(), $D->getMeta(), $D->getCount(), $D->getName()];
            $EData = [$E->getId(), $E->getMeta(), $E->getCount(), $E->getName()];
            $FData = [$F->getId(), $F->getMeta(), $F->getCount(), $F->getName()];
            $GData = [$G->getId(), $G->getMeta(), $G->getCount(), $G->getName()];
            $HData = [$H->getId(), $H->getMeta(), $H->getCount(), $H->getName()];
            $IData = [$I->getId(), $I->getMeta(), $I->getCount(), $I->getName()];
            if($R->hasEnchantments())
            {
              $RData = [$R->getId(), $R->getMeta(), $R->getCount(), $R->getName(), "null", serialize($R->getNamedTag())];
            }else{
              $RData = [$R->getId(), $R->getMeta(), $R->getCount(), $R->getName()];
            }
            $array = [$AData, $BData, $CData, $DData, $EData, $FData, $GData, $HData, $IData, $RData];
            $number = count($this->api->getAllRecipes()) + 1;
            $name = "Recipe-$number";
            $recipeFile = $this->source->getInstance()->getRecipeFile($name);
            $recipeFile->setNested("Recipe", $array);
            $recipeFile->save();
            $player->removeCurrentWindow();
          }
          return $transaction->discard();
        }
        
        return $transaction->continue();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function SkillMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 129 && $itemOutMeta === 0)
        {
          $this->MinerSkillMenu($player, 1);
        }elseif($itemOutId === 296 && $itemOutMeta === 0)
        {
          $this->FarmerSkillMenu($player, 1);
        }elseif($itemOutId === 17 && $itemOutMeta === 0)
        {
          $this->LumberjackSkillMenu($player, 1);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(129, 0, 1)->setCustomName("§r §bMiner §3Skill §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(296, 0, 1)->setCustomName("§r §bFarmer §3Skill §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r §bLumberjack §3Skill §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function MinerSkillMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    if($page === 1)
    {
      $menu->setListener(
        function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
        {
          $itemIn = $transaction->getIn();
          $itemOut = $transaction->getOut();
          $player = $transaction->getPlayer();
          $itemInId = $transaction->getIn()->getId();
          $itemOutId = $transaction->getOut()->getId();
          $itemInMeta = $transaction->getIn()->getMeta();
          $inv = $transaction->getAction()->getInventory();
          $itemOutMeta = $transaction->getOut()->getMeta();
          $playerName = $transaction->getPlayer()->getName();
          $itemInName = $transaction->getIn()->getCustomName();
          $itemOutName = $transaction->getOut()->getCustomName();
          
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->SkillMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160 && $itemOutMeta === 5)
        {
          $this->MinerSkillMenu($player, 2);
        }
          
          return $transaction->discard();
        }
      );
      $playerLevel = $this->api->getLevel($player, "Miner");
      $playerXp = $this->api->changeNumericFormat($this->api->getXp($player, "Miner"), "k");
      $requiredXp = $this->api->changeNumericFormat(($playerLevel * $this->config->get("XpPerLevel")), "k");
      if($playerLevel >= 1)
      {
        $level1achieved = true;
        $level1Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 1 §r\n§r §7Achieved §r");
      }else{
        $level1achieved = false;
        $level1Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §aLevel - 1 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }
      if($playerLevel >= 2)
      {
        $level2achieved = true;
        $level2Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 2 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMine FastTravel §r\n§r §7- §e+ 1,000 coins §r");
      }elseif($level1achieved)
      {
        $level2achieved = false;
        $level2Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 2 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMine FastTravel §r\n§r §7- §e+ 1,000 coins §r");
      }else{
        $level2achieved = false;
        $level2Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 2 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMine FastTravel §r\n§r §7- §e+ 1,000 coins §r");
      }
      if($playerLevel >= 3)
      {
        $level3achieved = true;
        $level3Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 3 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eCoal Worker Recipe §r\n§r §7- §eEnchanted Coal Recipe §r\n§r §7- §e+ 2,000 coins §r");
      }elseif($level2achieved)
      {
        $level3achieved = false;
        $level3Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 3 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eCoal Worker Recipe §r\n§r §7- §eEnchanted Coal Recipe §r\n§r §7- §e+ 2,000 coins §r");
      }else{
        $level3achieved = false;
        $level3Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 3 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eCoal Worker Recipe §r\n§r §7- §eEnchanted Coal Recipe §r\n§r §7- §e+ 2,000 coins §r");
      }
      if($playerLevel >= 4)
      {
        $level4achieved = true;
        $level4Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 4 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eIron Worker Recipe §r\n§r §7- §eEnchanted Iron Recipe §r\n§r §7- §e+ 3,000 coins §r");
      }elseif($level3achieved)
      {
        $level4achieved = false;
        $level4Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 4 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eIron Worker Recipe §r\n§r §7- §eEnchanted Iron Recipe §r\n§r §7- §e+ 3,000 coins §r");
      }else{
        $level4achieved = false;
        $level4Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 4 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eIron Worker Recipe §r\n§r §7- §eEnchanted Iron Recipe §r\n§r §7- §e+ 3,000 coins §r");
      }
      if($playerLevel >= 5)
      {
        $level5achieved = true;
        $level5Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 5 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMiner Armor Recipe §r\n§r §7- §eLapis Worker Recipe §r\n§r §7- §eEnchanted Lapis Recipe §r\n§r §7- §e+ 3,600 coins §r");
      }elseif($level4achieved)
      {
        $level5achieved = false;
        $level5Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 5 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMiner Armor Recipe §r\n§r §7- §eLapis Worker Recipe §r\n§r §7- §eEnchanted Lapis Recipe §r\n§r §7- §e+ 3,600 coins §r");
      }else{
        $level5achieved = false;
        $level5Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 5 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMiner Armor Recipe §r\n§r §7- §eLapis Worker Recipe §r\n§r §7- §eEnchanted Lapis Recipe §r\n§r §7- §e+ 3,600 coins §r");
      }
      if($playerLevel >= 6)
      {
        $level6achieved = true;
        $level6Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 6 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGold Worker Recipe §r\n§r §7- §eEnchanted Gold Recipe §r\n§r §7- §e+ 5,250 coins §r");
      }elseif($level5achieved)
      {
        $level6achieved = false;
        $level6Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 6 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGold Worker Recipe §r\n§r §7- §eEnchanted Gold Recipe §r\n§r §7- §e+ 5,250 coins §r");
      }else{
        $level6achieved = false;
        $level6Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 6 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGold Worker Recipe §r\n§r §7- §eEnchanted Gold Recipe §r\n§r §7- §e+ 5,250 coins §r");
      }
      if($playerLevel >= 7)
      {
        $level7achieved = true;
        $level7Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 7 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMiner Pickaxe Recipe §r\n§r §7- §eEmerald Worker Recipe §r\n§r §7- §eEnchanted Emerald Recipe §r\n§r §7- §e+ 6,900 coins §r");
      }elseif($level6achieved)
      {
        $level7achieved = false;
        $level7Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 7 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMiner Pickaxe Recipe §r\n§r §7- §eEmerald Worker Recipe §r\n§r §7- §eEnchanted Emerald Recipe §r\n§r §7- §e+ 6,900 coins §r");
      }else{
        $level7achieved = false;
        $level7Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 7 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMiner Pickaxe Recipe §r\n§r §7- §eEmerald Worker Recipe §r\n§r §7- §eEnchanted Emerald Recipe §r\n§r §7- §e+ 6,900 coins §r");
      }
      if($playerLevel >= 8)
      {
        $level8achieved = true;
        $level8Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 8 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eBazaar (33.3%) §r\n§r §7- §eDiamond Worker Recipe §r\n§r §7- §eEnchanted Diamond Recipe §r\n§r §7- §e+ 7,200 coins §r");
      }elseif($level7achieved)
      {
        $level8achieved = false;
        $level8Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 8 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eBazaar (33.3%) §r\n§r §7- §eDiamond Worker Recipe §r\n§r §7- §eEnchanted Diamond Recipe §r\n§r §7- §e+ 7,200 coins §r");
      }else{
        $level8achieved = false;
        $level8Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 8 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eBazaar (33.3%) §r\n§r §7- §eDiamond Worker Recipe §r\n§r §7- §eEnchanted Diamond Recipe §r\n§r §7- §e+ 7,200 coins §r");
      }
      if($playerLevel >= 9)
      {
        $level9achieved = true;
        $level9Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 9 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eDiamon Spreading Recipe §r\n§r §7- §e+ 8,400 coins §r");
      }elseif($level8achieved)
      {
        $level9achieved = false;
        $level9Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 9 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eDiamon Spreading Recipe §r\n§r §7- §e+ 8,400 coins §r");
      }else{
        $level9achieved = false;
        $level9Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 9 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eDiamond Spreading Recipe §r\n§r §7- §e+ 8,400 coins §r");
      }
      if($playerLevel >= 10)
      {
        $level10achieved = true;
        $level10Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 10 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eCompactor Recipe §r\n§r §7- §e+ 9,134 coins §r");
      }elseif($level9achieved)
      {
        $level10achieved = false;
        $level10Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 10 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eCompactor Recipe §r\n§r §7- §e+ 9,134 coins §r");
      }else{
        $level10achieved = false;
        $level10Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 10 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eCompactor Recipe §r\n§r §7- §e+ 9,134 coins §r");
      }
      if($playerLevel >= 11)
      {
        $level11achieved = true;
        $level11Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 11 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eRock Pet §r\n§r §7- §e+ 10,000 coins §r");
      }elseif($level10achieved)
      {
        $level11achieved = false;
        $level11Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 11 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eRock Pet §r\n§r §7- §e+ 10,000 coins §r");
      }else{
        $level11achieved = false;
        $level11Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 11 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eRock Pet §r\n§r §7- §e+ 10,000 coins §r");
      }
      if($playerLevel >= 12)
      {
        $level12achieved = true;
        $level12Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 12 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMining Bag §r\n§r §7- §e+ 11,278 coins §r");
      }elseif($level11achieved)
      {
        $level12achieved = false;
        $level12Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 12 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMining Bag §r\n§r §7- §e+ 11,278 coins §r");
      }else{
        $level12achieved = false;
        $level12Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 12 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMining Bag §r\n§r §7- §e+ 11,278 coins §r");
      }
      if($playerLevel >= 13)
      {
        $level13achieved = true;
        $level13Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 13 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMithril Crystal  §r\n§r §7- §e+ 12,450 coins §r");
      }elseif($level12achieved)
      {
        $level13achieved = false;
        $level13Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 13 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMithril Crystal  §r\n§r §7- §e+ 12,450 coins §r");
      }else{
        $level13achieved = false;
        $level13Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 13 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMithril Crystal Recipe  §r\n§r §7- §e+ 12,450 coins §r");
      }
      if($playerLevel >= 14)
      {
        $level14achieved = true;
        $level14Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 14 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMithril Pickaxe Recipe §r\n§r §7- §e+ 13,541 coins §r");
      }elseif($level13achieved)
      {
        $level14achieved = false;
        $level14Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 14 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMithril Pickaxe Recipe §r\n§r §7- §e+ 13,541 coins §r");
      }else{
        $level14achieved = false;
        $level14Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 14 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMithril Pickaxe Recipe §r\n§r §7- §e+ 13,541 coins §r");
      }
      if($playerLevel >= 15)
      {
        $level15achieved = true;
        $level15Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 15 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §ePower Scroll Recipe §r\n§r §7- §e+ 13,987 coins §r");
      }elseif($level14achieved)
      {
        $level15achieved = false;
        $level15Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 15 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §ePower Scroll Recipe §r\n§r §7- §e+ 13,987 coins §r");
      }else{
        $level15achieved = false;
        $level15Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 15 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §ePower Scroll Recipe §r\n§r §7- §e+ 13,987 coins §r");
      }
      if($playerLevel >= 16)
      {
        $level16achieved = true;
        $level16Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 16 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eEnchanted Mithril Recipe §r\n§r §7- §e+ 14,786 coins §r");
      }elseif($level15achieved)
      {
        $level16achieved = false;
        $level16Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 16 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eEnchanted Mithril Recipe §r\n§r §7- §e+ 14,786 coins §r");
      }else{
        $level16achieved = false;
        $level16Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 16 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eEnchanted Mithril Recipe §r\n§r §7- §e+ 14,786 coins §r");
      }
      $inv = $menu->getInventory();
      $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(9, ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §bMiner §3Skill §r"));
      $inv->setItem(10, $level1Item);
      $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(12, $level7Item);
      $inv->setItem(13, $level8Item);
      $inv->setItem(14, $level9Item);
      $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(16, $level15Item);
      $inv->setItem(17, $level16Item);
      $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(19, $level2Item);
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, $level6Item);
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, $level10Item);
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, $level14Item);
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(28, $level3Item);
      $inv->setItem(29, $level4Item);
      $inv->setItem(30, $level5Item);
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(32, $level11Item);
      $inv->setItem(33, $level12Item);
      $inv->setItem(34, $level13Item);
      $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
      $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
      $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $menu->setListener(
        function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
        {
          $itemIn = $transaction->getIn();
          $itemOut = $transaction->getOut();
          $player = $transaction->getPlayer();
          $itemInId = $transaction->getIn()->getId();
          $itemOutId = $transaction->getOut()->getId();
          $itemInMeta = $transaction->getIn()->getMeta();
          $inv = $transaction->getAction()->getInventory();
          $itemOutMeta = $transaction->getOut()->getMeta();
          $playerName = $transaction->getPlayer()->getName();
          $itemInName = $transaction->getIn()->getCustomName();
          $itemOutName = $transaction->getOut()->getCustomName();
          
          if($itemOutId === 331 && $itemOutMeta === 0)
          {
            $player->removeCurrentWindow();
          }elseif($itemOutId === 160 && $itemOutMeta === 4)
          {
            $this->MinerSkillMenu($player, 1);
          }
          
          return $transaction->discard();
        }
      );
      $playerLevel = $this->api->getLevel($player, "Miner");
      $playerXp = $this->api->changeNumericFormat($this->api->getXp($player, "Miner"), "k");
      $requiredXp = $this->api->changeNumericFormat(($playerLevel * $this->config->get("XpPerLevel")), "k");
      if($playerLevel >= 17)
      {
        $level17achieved = true;
        $level17Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 17 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eReforging §r\n§r §7- §e+ 15,980 coins §r");
      }elseif($playerLevel >= 16)
      {
        $level17achieved = false;
        $level17Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §aLevel - 17 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eReforging §r\n§r §7- §e+ 15,980 coins §r");
      }else{
        $level17achieved = false;
        $level17Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 17 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eReforging §r\n§r §7- §e+ 15,980 coins §r");
      }
      if($playerLevel >= 18)
      {
        $level18achieved = true;
        $level18Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 18 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eSlimy Pet §r\n§r §7- §e+ 16,869 coins §r");
      }elseif($level17achieved)
      {
        $level18achieved = false;
        $level18Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 18 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eSlimy Pet §r\n§r §7- §e+ 16,869 coins §r");
      }else{
        $level18achieved = false;
        $level18Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 18 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eSlimy Pet §r\n§r §7- §e+ 16,869 coins §r");
      }
      if($playerLevel >= 19)
      {
        $level19achieved = true;
        $level19Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 19 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eTreasure Finder §r\n§r §7- §e+ 17,960 coins §r");
      }elseif($level18achieved)
      {
        $level19achieved = false;
        $level19Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 19 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eTreasure Finder §r\n§r §7- §e+ 17,960 coins §r");
      }else{
        $level19achieved = false;
        $level19Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 19 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eTreasure Finder §r\n§r §7- §e+ 17,960 coins §r");
      }
      if($playerLevel >= 20)
      {
        $level20achieved = true;
        $level20Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 20 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eWand Recipe §r\n§r §7- §e+ 19,007 coins §r");
      }elseif($level19achieved)
      {
        $level20achieved = false;
        $level20Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 20 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eWand Recipe §r\n§r §7- §e+ 19,007 coins §r");
      }else{
        $level20achieved = false;
        $level20Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 20 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eWand Recipe §r\n§r §7- §e+ 19,007 coins §r");
      }
      if($playerLevel >= 21)
      {
        $level21achieved = true;
        $level21Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 21 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eWand Of Mending Recipe §r\n§r §7- §eWand Of Healing Recipe §r\n§r §7- §e+ 20,170 coins §r");
      }elseif($level20achieved)
      {
        $level21achieved = false;
        $level21Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 21 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eWand Of Mending Recipe §r\n§r §7- §eWand Of Healing Recipe §r\n§r §7- §e+ 20,170 coins §r");
      }else{
        $level21achieved = false;
        $level21Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 21 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eWand Of Mending Recipe §r\n§r §7- §eWand Of Healing Recipe §r\n§r §7- §e+ 20,170 coins §r");
      }
      if($playerLevel >= 22)
      {
        $level22achieved = true;
        $level22Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 22 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eLapis Armor Recipe §r\n§r §7- §e+ 21,164 coins §r");
      }elseif($level21achieved)
      {
        $level22achieved = false;
        $level22Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 22 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eLapis Armor Recipe §r\n§r §7- §e+ 21,164 coins §r");
      }else{
        $level22achieved = false;
        $level22Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 22 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eLapis Armor Recipe §r\n§r §7- §e+ 21,164 coins §r");
      }
      if($playerLevel >= 23)
      {
        $level23achieved = true;
        $level23Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 23 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eEmerald Armor Recipe §r\n§r §7- §e+ 22,019 coins §r");
      }elseif($level22achieved)
      {
        $level23achieved = false;
        $level23Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 23 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eEmerald Armor Recipe §r\n§r §7- §e+ 22,019 coins §r");
      }else{
        $level23achieved = false;
        $level23Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 23 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eEmerald Armor Recipe §r\n§r §7- §e+ 22,019 coins §r");
      }
      if($playerLevel >= 24)
      {
        $level24achieved = true;
        $level24Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 24 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eRedstone Armor Recipe §r\n§r §7- §e+ 22,969 coins §r");
      }elseif($level23achieved)
      {
        $level24achieved = false;
        $level24Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 24 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eRedstone Armor Recipe §r\n§r §7- §e+ 22,969 coins §r");
      }else{
        $level24achieved = false;
        $level24Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 24 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eRedstone Armor Recipe §r\n§r §7- §e+ 22,969 coins §r");
      }
      if($playerLevel >= 25)
      {
        $level25achieved = true;
        $level25Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 25 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMining Xp Boost §r\n§r §7- §e+ 23,868 coins §r");
      }elseif($level24achieved)
      {
        $level25achieved = false;
        $level25Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 25 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMining Xp Boost §r\n§r §7- §e+ 23,868 coins §r");
      }else{
        $level25achieved = false;
        $level25Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 25 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMining Xp Boost §r\n§r §7- §e+ 23,868 coins §r");
      }
      if($playerLevel >= 26)
      {
        $level26achieved = true;
        $level26Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 26 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGrappling Hook Recipe §r\n§r §7- §e+ 24,907 coins §r");
      }elseif($level25achieved)
      {
        $level26achieved = false;
        $level26Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 26 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGrappling Hook Recipe §r\n§r §7- §e+ 24,907 coins §r");
      }else{
        $level26achieved = false;
        $level26Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 26 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGrappling Hook Recipe §r\n§r §7- §e+ 24,907 coins §r");
      }
      if($playerLevel >= 27)
      {
        $level27achieved = true;
        $level27Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 27 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §ePortal Of Deep Cavans Recipe §r\n§r §7- §eHarden Diamond Recipe §r\n§r §7- §eHarden Diamond Armor §r\n§r §7- §e+ 26,003 coins §r");
      }elseif($level26achieved)
      {
        $level27achieved = false;
        $level27Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 27 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §ePortal Of Deep Cavans Recipe §r\n§r §7- §eHarden Diamond Recipe §r\n§r §7- §eHarden Diamond Armor §r\n§r §7- §e+ 26,003 coins §r");
      }else{
        $level27achieved = false;
        $level27Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 27 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §ePortal Of Deep Cavans Recipe §r\n§r §7- §eHarden Diamond Recipe §r\n§r §7- §eHarden Diamond Armor §r\n§r §7- §e+ 26,003 coins §r");
      }
      if($playerLevel >= 28)
      {
        $level28achieved = true;
        $level28Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 28 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eDragon Armor Recipe §r\n§r §7- §e+ 27,024 coins §r");
      }elseif($level27achieved)
      {
        $level28achieved = false;
        $level28Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 28 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eDragon Armor Recipe §r\n§r §7- §e+ 27,024 coins §r");
      }else{
        $level28achieved = false;
        $level28Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 28 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eDragon Armor Recipe §r\n§r §7- §e+ 27,024 coins §r");
      }
      if($playerLevel >= 29)
      {
        $level29achieved = true;
        $level29Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 29 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eFire Talisman Recipe §r\n§r §7- §eMagnetic Talisman Recipe §r\n§r §7- §e+ 27,800 coins §r");
      }elseif($level28achieved)
      {
        $level29achieved = false;
        $level29Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 29 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eFire Talisman Recipe §r\n§r §7- §eMagnetic Talisman Recipe §r\n§r §7- §e+ 27,800 coins §r");
      }else{
        $level29achieved = false;
        $level29Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 29 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eFire Talisman Recipe §r\n§r §7- §eMagnetic Talisman Recipe §r\n§r §7- §e+ 27,800 coins §r");
      }
      if($playerLevel >= 30)
      {
        $level30achieved = true;
        $level30Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 30 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMagical Water Bucket Recipe §r\n§r §7- §eMagical Lava Bucket Recipe §r\n§r §7- §eTalisman Of Power Recipe §r\n§r §7- §e+ 28806 coins §r");
      }elseif($level29achieved)
      {
        $level30achieved = false;
        $level30Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 30 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMagical Water Bucket Recipe §r\n§r §7- §eMagical Lava Bucket Recipe §r\n§r §7- §eTalisman Of Power Recipe §r\n§r §7- §e+ 28806 coins §r");
      }else{
        $level30achieved = false;
        $level30Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 30 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eMagical Water Bucket Recipe §r\n§r §7- §eMagical Lava Bucket Recipe §r\n§r §7- §eTalisman Of Power Recipe §r\n§r §7- §e+ 28806 coins §r");
      }
      if($playerLevel >= 31)
      {
        $level31achieved = true;
        $level31Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 31 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGravity Talisman Recipe §r\n§r §7- §eMithril Pet §r\n§r §7- §e+ 30,620 coins §r");
      }elseif($level30achieved)
      {
        $level31achieved = false;
        $level31Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 31 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGravity Talisman Recipe §r\n§r §7- §eMithril Pet §r\n§r §7- §e+ 30,620 coins §r");
      }else{
        $level31achieved = false;
        $level31Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 31 §r\n§r §7Locked §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGravity Talisman Recipe §r\n§r §7- §eMithril Pet §r\n§r §7- §e+ 30,620 coins §r");
      }
      if($playerLevel >= 32)
      {
        $level32achieved = true;
        $level32Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 32 §r\n§r §7Achieved §r\n§r §7 §r\n§r §7Rewards:- §r\n§r §7- §eGod Potion Recipe §r\n§r §7- §eSuperior Armor Recipe §r\n§r §7- §e+ 32,000 coins §r");
      }elseif($level31achieved)
      {
        $level32achieved = false;
        $level32Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 32 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level32achieved = false;
        $level32Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 32 §r\n§r §7Locked §r");
      }
      $inv = $menu->getInventory();
      $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(9, $level17Item);
      $inv->setItem(10, $level18Item);
      $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(12, $level24Item);
      $inv->setItem(13, $level25Item);
      $inv->setItem(14, $level26Item);
      $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(16, $level32Item);
      $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(19, $level19Item);
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, $level23Item);
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, $level27Item);
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, $level31Item);
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(28, $level20Item);
      $inv->setItem(29, $level21Item);
      $inv->setItem(30, $level22Item);
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(32, $level28Item);
      $inv->setItem(33, $level29Item);
      $inv->setItem(34, $level30Item);
      $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrevious Page §r"));
      $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
      $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function FarmerSkillMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    if($page === 1)
    {
      $menu->setListener(
        function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
        {
          $itemIn = $transaction->getIn();
          $itemOut = $transaction->getOut();
          $player = $transaction->getPlayer();
          $itemInId = $transaction->getIn()->getId();
          $itemOutId = $transaction->getOut()->getId();
          $itemInMeta = $transaction->getIn()->getMeta();
          $inv = $transaction->getAction()->getInventory();
          $itemOutMeta = $transaction->getOut()->getMeta();
          $playerName = $transaction->getPlayer()->getName();
          $itemInName = $transaction->getIn()->getCustomName();
          $itemOutName = $transaction->getOut()->getCustomName();
          
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->SkillMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160 && $itemOutMeta === 5)
        {
          $this->FarmerSkillMenu($player, 2);
        }
          
          return $transaction->discard();
        }
      );
      $playerLevel = $this->api->getLevel($player, "Farmer");
      $playerXp = $this->api->changeNumericFormat($this->api->getXp($player, "Farmer"), "k");
      $requiredXp = $this->api->changeNumericFormat(($playerLevel * $this->config->get("XpPerLevel")), "k");
      if($playerLevel >= 1)
      {
        $level1achieved = true;
        $level1Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 1 §r\n§r §7Achieved §r");
      }else{
        $level1achieved = false;
        $level1Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §aLevel - 1 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }
      if($playerLevel >= 2)
      {
        $level2achieved = true;
        $level2Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 2 §r\n§r §7Achieved §r");
      }elseif($level1achieved)
      {
        $level2achieved = false;
        $level2Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 2 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level2achieved = false;
        $level2Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 2 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 3)
      {
        $level3achieved = true;
        $level3Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 3 §r\n§r §7Achieved §r");
      }elseif($level2achieved)
      {
        $level3achieved = false;
        $level3Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 3 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level3achieved = false;
        $level3Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 3 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 4)
      {
        $level4achieved = true;
        $level4Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 4 §r\n§r §7Achieved §r");
      }elseif($level3achieved)
      {
        $level4achieved = false;
        $level4Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 4 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level4achieved = false;
        $level4Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 4 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 5)
      {
        $level5achieved = true;
        $level5Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 5 §r\n§r §7Achieved §r");
      }elseif($level4achieved)
      {
        $level5achieved = false;
        $level5Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 5 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level5achieved = false;
        $level5Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 5 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 6)
      {
        $level6achieved = true;
        $level6Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 6 §r\n§r §7Achieved §r");
      }elseif($level5achieved)
      {
        $level6achieved = false;
        $level6Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 6 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level6achieved = false;
        $level6Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 6 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 7)
      {
        $level7achieved = true;
        $level7Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 7 §r\n§r §7Achieved §r");
      }elseif($level6achieved)
      {
        $level7achieved = false;
        $level7Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 7 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level7achieved = false;
        $level7Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 7 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 8)
      {
        $level8achieved = true;
        $level8Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 8 §r\n§r §7Achieved §r");
      }elseif($level7achieved)
      {
        $level8achieved = false;
        $level8Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 8 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level8achieved = false;
        $level8Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 8 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 9)
      {
        $level9achieved = true;
        $level9Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 9 §r\n§r §7Achieved §r");
      }elseif($level8achieved)
      {
        $level9achieved = false;
        $level9Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 9 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level9achieved = false;
        $level9Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 9 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 10)
      {
        $level10achieved = true;
        $level10Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 10 §r\n§r §7Achieved §r");
      }elseif($level9achieved)
      {
        $level10achieved = false;
        $level10Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 10 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level10achieved = false;
        $level10Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 10 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 11)
      {
        $level11achieved = true;
        $level11Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 11 §r\n§r §7Achieved §r");
      }elseif($level10achieved)
      {
        $level11achieved = false;
        $level11Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 11 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level11achieved = false;
        $level11Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 11 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 12)
      {
        $level12achieved = true;
        $level12Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 12 §r\n§r §7Achieved §r");
      }elseif($level11achieved)
      {
        $level12achieved = false;
        $level12Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 12 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level12achieved = false;
        $level12Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 12 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 13)
      {
        $level13achieved = true;
        $level13Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 13 §r\n§r §7Achieved §r");
      }elseif($level12achieved)
      {
        $level13achieved = false;
        $level13Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 13 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level13achieved = false;
        $level13Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 13 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 14)
      {
        $level14achieved = true;
        $level14Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 14 §r\n§r §7Achieved §r");
      }elseif($level13achieved)
      {
        $level14achieved = false;
        $level14Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 14 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level14achieved = false;
        $level14Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 14 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 15)
      {
        $level15achieved = true;
        $level15Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 15 §r\n§r §7Achieved §r");
      }elseif($level14achieved)
      {
        $level15achieved = false;
        $level15Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 15 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level15achieved = false;
        $level15Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 15 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 16)
      {
        $level16achieved = true;
        $level16Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 16 §r\n§r §7Achieved §r");
      }elseif($level15achieved)
      {
        $level16achieved = false;
        $level16Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 16 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level16achieved = false;
        $level16Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 16 §r\n§r §7Locked §r");
      }
      $inv = $menu->getInventory();
      $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(9, ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §bFarmer §3Skill §r"));
      $inv->setItem(10, $level1Item);
      $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(12, $level7Item);
      $inv->setItem(13, $level8Item);
      $inv->setItem(14, $level9Item);
      $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(16, $level15Item);
      $inv->setItem(17, $level16Item);
      $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(19, $level2Item);
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, $level6Item);
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, $level10Item);
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, $level14Item);
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(28, $level3Item);
      $inv->setItem(29, $level4Item);
      $inv->setItem(30, $level5Item);
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(32, $level11Item);
      $inv->setItem(33, $level12Item);
      $inv->setItem(34, $level13Item);
      $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
      $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
      $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $menu->setListener(
        function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
        {
          $itemIn = $transaction->getIn();
          $itemOut = $transaction->getOut();
          $player = $transaction->getPlayer();
          $itemInId = $transaction->getIn()->getId();
          $itemOutId = $transaction->getOut()->getId();
          $itemInMeta = $transaction->getIn()->getMeta();
          $inv = $transaction->getAction()->getInventory();
          $itemOutMeta = $transaction->getOut()->getMeta();
          $playerName = $transaction->getPlayer()->getName();
          $itemInName = $transaction->getIn()->getCustomName();
          $itemOutName = $transaction->getOut()->getCustomName();
          
          if($itemOutId === 331 && $itemOutMeta === 0)
          {
            $player->removeCurrentWindow();
          }elseif($itemOutId === 160 && $itemOutMeta === 4)
          {
            $this->FarmerSkillMenu($player, 1);
          }
          
          return $transaction->discard();
        }
      );
      $playerLevel = $this->api->getLevel($player, "Farmer");
      $playerXp = $this->api->changeNumericFormat($this->api->getXp($player, "Farmer"), "k");
      $requiredXp = $this->api->changeNumericFormat(($playerLevel * $this->config->get("XpPerLevel")), "k");
      if($playerLevel >= 17)
      {
        $level17achieved = true;
        $level17Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 17 §r\n§r §7Achieved §r");
      }elseif($playerLevel >= 16)
      {
        $level17achieved = false;
        $level17Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §aLevel - 17 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level17achieved = false;
        $level17Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 17 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 18)
      {
        $level18achieved = true;
        $level18Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 18 §r\n§r §7Achieved §r");
      }elseif($level17achieved)
      {
        $level18achieved = false;
        $level18Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 18 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level18achieved = false;
        $level18Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 18 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 19)
      {
        $level19achieved = true;
        $level19Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 19 §r\n§r §7Achieved §r");
      }elseif($level18achieved)
      {
        $level19achieved = false;
        $level19Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 19 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level19achieved = false;
        $level19Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 19 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 20)
      {
        $level20achieved = true;
        $level20Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 20 §r\n§r §7Achieved §r");
      }elseif($level19achieved)
      {
        $level20achieved = false;
        $level20Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 20 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level20achieved = false;
        $level20Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 20 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 21)
      {
        $level21achieved = true;
        $level21Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 21 §r\n§r §7Achieved §r");
      }elseif($level20achieved)
      {
        $level21achieved = false;
        $level21Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 21 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level21achieved = false;
        $level21Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 21 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 22)
      {
        $level22achieved = true;
        $level22Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 22 §r\n§r §7Achieved §r");
      }elseif($level21achieved)
      {
        $level22achieved = false;
        $level22Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 22 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level22achieved = false;
        $level22Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 22 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 23)
      {
        $level23achieved = true;
        $level23Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 23 §r\n§r §7Achieved §r");
      }elseif($level22achieved)
      {
        $level23achieved = false;
        $level23Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 23 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level23achieved = false;
        $level23Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 23 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 24)
      {
        $level24achieved = true;
        $level24Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 24 §r\n§r §7Achieved §r");
      }elseif($level23achieved)
      {
        $level24achieved = false;
        $level24Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 24 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level24achieved = false;
        $level24Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 24 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 25)
      {
        $level25achieved = true;
        $level25Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 25 §r\n§r §7Achieved §r");
      }elseif($level24achieved)
      {
        $level25achieved = false;
        $level25Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 25 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level25achieved = false;
        $level25Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 25 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 26)
      {
        $level26achieved = true;
        $level26Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 26 §r\n§r §7Achieved §r");
      }elseif($level25achieved)
      {
        $level26achieved = false;
        $level26Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 26 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level26achieved = false;
        $level26Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 26 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 27)
      {
        $level27achieved = true;
        $level27Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 27 §r\n§r §7Achieved §r");
      }elseif($level26achieved)
      {
        $level27achieved = false;
        $level27Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 27 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level27achieved = false;
        $level27Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 27 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 28)
      {
        $level28achieved = true;
        $level28Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 28 §r\n§r §7Achieved §r");
      }elseif($level27achieved)
      {
        $level28achieved = false;
        $level28Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 28 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level28achieved = false;
        $level28Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 28 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 29)
      {
        $level29achieved = true;
        $level29Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 29 §r\n§r §7Achieved §r");
      }elseif($level28achieved)
      {
        $level29achieved = false;
        $level29Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 29 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level29achieved = false;
        $level29Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 29 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 30)
      {
        $level30achieved = true;
        $level30Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 30 §r\n§r §7Achieved §r");
      }elseif($level29achieved)
      {
        $level30achieved = false;
        $level30Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 30 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level30achieved = false;
        $level30Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 30 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 31)
      {
        $level31achieved = true;
        $level31Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 31 §r\n§r §7Achieved §r");
      }elseif($level30achieved)
      {
        $level31achieved = false;
        $level31Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 31 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level31achieved = false;
        $level31Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 31 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 32)
      {
        $level32achieved = true;
        $level32Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 32 §r\n§r §7Achieved §r");
      }elseif($level31achieved)
      {
        $level32achieved = false;
        $level32Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 32 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level32achieved = false;
        $level32Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 32 §r\n§r §7Locked §r");
      }
      $inv = $menu->getInventory();
      $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(9, $level17Item);
      $inv->setItem(10, $level18Item);
      $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(12, $level24Item);
      $inv->setItem(13, $level25Item);
      $inv->setItem(14, $level26Item);
      $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(16, $level32Item);
      $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(19, $level19Item);
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, $level23Item);
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, $level27Item);
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, $level31Item);
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(28, $level20Item);
      $inv->setItem(29, $level21Item);
      $inv->setItem(30, $level22Item);
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(32, $level28Item);
      $inv->setItem(33, $level29Item);
      $inv->setItem(34, $level30Item);
      $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrevious Page §r"));
      $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
      $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function LumberjackSkillMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    if($page === 1)
    {
      $menu->setListener(
        function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
        {
          $itemIn = $transaction->getIn();
          $itemOut = $transaction->getOut();
          $player = $transaction->getPlayer();
          $itemInId = $transaction->getIn()->getId();
          $itemOutId = $transaction->getOut()->getId();
          $itemInMeta = $transaction->getIn()->getMeta();
          $inv = $transaction->getAction()->getInventory();
          $itemOutMeta = $transaction->getOut()->getMeta();
          $playerName = $transaction->getPlayer()->getName();
          $itemInName = $transaction->getIn()->getCustomName();
          $itemOutName = $transaction->getOut()->getCustomName();
          
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->SkillMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160 && $itemOutMeta === 5)
        {
          $this->LumberjackSkillMenu($player, 2);
        }
          
          return $transaction->discard();
        }
      );
      $playerLevel = $this->api->getLevel($player, "Lumberjack");
      $playerXp = $this->api->changeNumericFormat($this->api->getXp($player, "Lumberjack"), "k");
      $requiredXp = $this->api->changeNumericFormat(($playerLevel * $this->config->get("XpPerLevel")), "k");
      if($playerLevel >= 1)
      {
        $level1achieved = true;
        $level1Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 1 §r\n§r §7Achieved §r");
      }else{
        $level1achieved = false;
        $level1Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §aLevel - 1 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }
      if($playerLevel >= 2)
      {
        $level2achieved = true;
        $level2Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 2 §r\n§r §7Achieved §r");
      }elseif($level1achieved)
      {
        $level2achieved = false;
        $level2Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 2 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Level 2 Reward:- §r\n§r §7- §e+ 1,000 coins §r\n§r §7- §eSmall PotionBag §r");
      }else{
        $level2achieved = false;
        $level2Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 2 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 3)
      {
        $level3achieved = true;
        $level3Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 3 §r\n§r §7Achieved §r");
      }elseif($level2achieved)
      {
        $level3achieved = false;
        $level3Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 3 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Level 3 Reward:- §r\n§r §7- §e+ 2,000 coins §r\n§r §7- §aForest FastTravel §r");
      }else{
        $level3achieved = false;
        $level3Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 3 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 4)
      {
        $level4achieved = true;
        $level4Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 4 §r\n§r §7Achieved §r");
      }elseif($level3achieved)
      {
        $level4achieved = false;
        $level4Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 4 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Level 4 Reward:- §r\n§r §7- §e+ 3,000 coins §r\n§r §7- §aLumberjack Worker §r");
      }else{
        $level4achieved = false;
        $level4Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 4 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 5)
      {
        $level5achieved = true;
        $level5Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 5 §r\n§r §7Achieved §r");
      }elseif($level4achieved)
      {
        $level5achieved = false;
        $level5Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 5 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Level 5 Reward:- §r\n§r §7- §e+ 4,000 coins §r\n§r §7- §aForest Portal §r");
      }else{
        $level5achieved = false;
        $level5Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 5 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 6)
      {
        $level6achieved = true;
        $level6Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 6 §r\n§r §7Achieved §r");
      }elseif($level5achieved)
      {
        $level6achieved = false;
        $level6Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 6 §r\n§r §7Progress: §8$playerXp/$requiredXp §r\n§r §7 §r\n§r §7Level 6 Reward:- §r\n§r §7- §e+ 5,000 coins §r\n§r §7- §eLarge PotionBag §r");
      }else{
        $level6achieved = false;
        $level6Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 6 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 7)
      {
        $level7achieved = true;
        $level7Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 7 §r\n§r §7Achieved §r");
      }elseif($level6achieved)
      {
        $level7achieved = false;
        $level7Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 7 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level7achieved = false;
        $level7Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 7 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 8)
      {
        $level8achieved = true;
        $level8Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 8 §r\n§r §7Achieved §r");
      }elseif($level7achieved)
      {
        $level8achieved = false;
        $level8Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 8 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level8achieved = false;
        $level8Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 8 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 9)
      {
        $level9achieved = true;
        $level9Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 9 §r\n§r §7Achieved §r");
      }elseif($level8achieved)
      {
        $level9achieved = false;
        $level9Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 9 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level9achieved = false;
        $level9Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 9 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 10)
      {
        $level10achieved = true;
        $level10Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 10 §r\n§r §7Achieved §r");
      }elseif($level9achieved)
      {
        $level10achieved = false;
        $level10Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 10 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level10achieved = false;
        $level10Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 10 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 11)
      {
        $level11achieved = true;
        $level11Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 11 §r\n§r §7Achieved §r");
      }elseif($level10achieved)
      {
        $level11achieved = false;
        $level11Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 11 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level11achieved = false;
        $level11Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 11 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 12)
      {
        $level12achieved = true;
        $level12Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 12 §r\n§r §7Achieved §r");
      }elseif($level11achieved)
      {
        $level12achieved = false;
        $level12Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 12 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level12achieved = false;
        $level12Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 12 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 13)
      {
        $level13achieved = true;
        $level13Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 13 §r\n§r §7Achieved §r");
      }elseif($level12achieved)
      {
        $level13achieved = false;
        $level13Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 13 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level13achieved = false;
        $level13Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 13 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 14)
      {
        $level14achieved = true;
        $level14Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 14 §r\n§r §7Achieved §r");
      }elseif($level13achieved)
      {
        $level14achieved = false;
        $level14Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 14 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level14achieved = false;
        $level14Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 14 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 15)
      {
        $level15achieved = true;
        $level15Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 15 §r\n§r §7Achieved §r");
      }elseif($level14achieved)
      {
        $level15achieved = false;
        $level15Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 15 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level15achieved = false;
        $level15Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 15 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 16)
      {
        $level16achieved = true;
        $level16Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 16 §r\n§r §7Achieved §r");
      }elseif($level15achieved)
      {
        $level16achieved = false;
        $level16Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 16 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level16achieved = false;
        $level16Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 16 §r\n§r §7Locked §r");
      }
      $inv = $menu->getInventory();
      $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(9, ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §bLumberjack §3Skill §r"));
      $inv->setItem(10, $level1Item);
      $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(12, $level7Item);
      $inv->setItem(13, $level8Item);
      $inv->setItem(14, $level9Item);
      $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(16, $level15Item);
      $inv->setItem(17, $level16Item);
      $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(19, $level2Item);
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, $level6Item);
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, $level10Item);
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, $level14Item);
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(28, $level3Item);
      $inv->setItem(29, $level4Item);
      $inv->setItem(30, $level5Item);
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(32, $level11Item);
      $inv->setItem(33, $level12Item);
      $inv->setItem(34, $level13Item);
      $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
      $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
      $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $menu->setListener(
        function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
        {
          $itemIn = $transaction->getIn();
          $itemOut = $transaction->getOut();
          $player = $transaction->getPlayer();
          $itemInId = $transaction->getIn()->getId();
          $itemOutId = $transaction->getOut()->getId();
          $itemInMeta = $transaction->getIn()->getMeta();
          $inv = $transaction->getAction()->getInventory();
          $itemOutMeta = $transaction->getOut()->getMeta();
          $playerName = $transaction->getPlayer()->getName();
          $itemInName = $transaction->getIn()->getCustomName();
          $itemOutName = $transaction->getOut()->getCustomName();
          
        if($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160 && $itemOutMeta === 4)
        {
          $this->LumberjackSkillMenu($player, 1);
        }
          
          return $transaction->discard();
        }
      );
      $playerLevel = $this->api->getLevel($player, "Lumberjack");
      $playerXp = $this->api->changeNumericFormat($this->api->getXp($player, "Lumberjack"), "k");
      $requiredXp = $this->api->changeNumericFormat(($playerLevel * $this->config->get("XpPerLevel")), "k");
      if($playerLevel >= 17)
      {
        $level17achieved = true;
        $level17Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 17 §r\n§r §7Achieved §r");
      }elseif($playerLevel >= 16)
      {
        $level17achieved = false;
        $level17Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §aLevel - 17 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level17achieved = false;
        $level17Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 17 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 18)
      {
        $level18achieved = true;
        $level18Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 18 §r\n§r §7Achieved §r");
      }elseif($level17achieved)
      {
        $level18achieved = false;
        $level18Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 18 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level18achieved = false;
        $level18Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 18 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 19)
      {
        $level19achieved = true;
        $level19Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 19 §r\n§r §7Achieved §r");
      }elseif($level18achieved)
      {
        $level19achieved = false;
        $level19Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 19 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level19achieved = false;
        $level19Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 19 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 20)
      {
        $level20achieved = true;
        $level20Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 20 §r\n§r §7Achieved §r");
      }elseif($level19achieved)
      {
        $level20achieved = false;
        $level20Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 20 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level20achieved = false;
        $level20Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 20 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 21)
      {
        $level21achieved = true;
        $level21Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 21 §r\n§r §7Achieved §r");
      }elseif($level20achieved)
      {
        $level21achieved = false;
        $level21Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 21 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level21achieved = false;
        $level21Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 21 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 22)
      {
        $level22achieved = true;
        $level22Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 22 §r\n§r §7Achieved §r");
      }elseif($level21achieved)
      {
        $level22achieved = false;
        $level22Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 22 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level22achieved = false;
        $level22Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 22 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 23)
      {
        $level23achieved = true;
        $level23Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 23 §r\n§r §7Achieved §r");
      }elseif($level22achieved)
      {
        $level23achieved = false;
        $level23Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 23 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level23achieved = false;
        $level23Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 23 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 24)
      {
        $level24achieved = true;
        $level24Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 24 §r\n§r §7Achieved §r");
      }elseif($level23achieved)
      {
        $level24achieved = false;
        $level24Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 24 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level24achieved = false;
        $level24Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 24 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 25)
      {
        $level25achieved = true;
        $level25Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 25 §r\n§r §7Achieved §r");
      }elseif($level24achieved)
      {
        $level25achieved = false;
        $level25Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 25 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level25achieved = false;
        $level25Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 25 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 26)
      {
        $level26achieved = true;
        $level26Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 26 §r\n§r §7Achieved §r");
      }elseif($level25achieved)
      {
        $level26achieved = false;
        $level26Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 26 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level26achieved = false;
        $level26Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 26 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 27)
      {
        $level27achieved = true;
        $level27Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 27 §r\n§r §7Achieved §r");
      }elseif($level26achieved)
      {
        $level27achieved = false;
        $level27Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 27 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level27achieved = false;
        $level27Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 27 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 28)
      {
        $level28achieved = true;
        $level28Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 28 §r\n§r §7Achieved §r");
      }elseif($level27achieved)
      {
        $level28achieved = false;
        $level28Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 28 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level28achieved = false;
        $level28Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 28 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 29)
      {
        $level29achieved = true;
        $level29Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 29 §r\n§r §7Achieved §r");
      }elseif($level28achieved)
      {
        $level29achieved = false;
        $level29Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 29 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level29achieved = false;
        $level29Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 29 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 30)
      {
        $level30achieved = true;
        $level30Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 30 §r\n§r §7Achieved §r");
      }elseif($level29achieved)
      {
        $level30achieved = false;
        $level30Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 30 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level30achieved = false;
        $level30Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 30 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 31)
      {
        $level31achieved = true;
        $level31Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 31 §r\n§r §7Achieved §r");
      }elseif($level30achieved)
      {
        $level31achieved = false;
        $level31Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 31 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level31achieved = false;
        $level31Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 31 §r\n§r §7Locked §r");
      }
      if($playerLevel >= 32)
      {
        $level32achieved = true;
        $level32Item = ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aLevel - 32 §r\n§r §7Achieved §r");
      }elseif($level31achieved)
      {
        $level32achieved = false;
        $level32Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 32 §r\n§r §7Progress: §8$playerXp/$requiredXp §r");
      }else{
        $level32achieved = false;
        $level32Item = ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cLevel - 32 §r\n§r §7Locked §r");
      }
      $inv = $menu->getInventory();
      $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(9, $level17Item);
      $inv->setItem(10, $level18Item);
      $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(12, $level24Item);
      $inv->setItem(13, $level25Item);
      $inv->setItem(14, $level26Item);
      $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(16, $level32Item);
      $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(19, $level19Item);
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, $level23Item);
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, $level27Item);
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, $level31Item);
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(28, $level20Item);
      $inv->setItem(29, $level21Item);
      $inv->setItem(30, $level22Item);
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(32, $level28Item);
      $inv->setItem(33, $level29Item);
      $inv->setItem(34, $level30Item);
      $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrevious Page §r"));
      $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
      $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function BankMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 54 && $itemOutMeta === 0)
        {
          $this->DepositMenu($player);
        }elseif($itemOutId === 125 && $itemOutMeta === 0)
        {
          $this->WhitdrawMenu($player);
        }elseif($itemOutId === 145 && $itemOutMeta === 0)
        {
          if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner")
          {
            $this->LoanMenu($player);
          }else{
            $player->sendMessage("§conly island owner and co-owner(s) can access loan menu");
          }
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $bankMoney = $this->api->getBankMoney($player);
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §bProfile §r\n§r §bHolder§7: §e".$player->getName()." §r\n§r §bBank§7: §e$bankMoney §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eDeposit §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(125, 0, 1)->setCustomName("§r §eWhitdraw §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(145, 0, 1)->setCustomName("§r §eLoan §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function DepositMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 54 && $itemOut->getCount() === 32)
        {
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $economy->getPlayerBalance($player->getName(),
              ClosureContext::create(
                function (?int $balance) use($player, $economy): void
                {
                  if(!is_null($balance))
                  {
                    $bankMoney = $this->api->getBankMoney($player);
                    $addingMoney = $balance/2;
                    if($addingMoney >= 1)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $addingMoney);
                      $this->api->addBankMoney($player, $addingMoney);
                      $player->sendMessage("§aSuccessfully added §e$addingMoney$ §ato your bank account");
                      if($this->api->getObjective($player) === "Deposit-Money")
                      {
                        $nextObjective = $this->api->getNextObjective($player);
                        $this->api->setObjective($player, $nextObjective);
                      }
                    }else{
                      $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                    }
                   }else{
                    $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                  }
                },
              ));
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 323 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
          $this->source->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function () use ($player): void 
            {
              $this->source->getInstance()->getUI()->CustomDepositMenu($player);
            }
          ), 8);
        }elseif($itemOutId === 54 && $itemOut->getCount() === 64)
        {
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $economy->getPlayerBalance($player->getName(),
              ClosureContext::create(
                function (?int $balance) use($player, $economy): void
                {
                  if(!is_null($balance))
                  {
                    $bankMoney = $this->api->getBankMoney($player);
                    $addingMoney = $balance;
                    if($addingMoney >= 1)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $addingMoney);
                      $this->api->addBankMoney($player, $addingMoney);
                      $player->sendMessage("§aSuccessfully added §e$addingMoney$ §ato your bank account");
                      if($this->api->getObjective($player) === "Deposit-Money")
                      {
                        $nextObjective = $this->api->getNextObjective($player);
                        $this->api->setObjective($player, $nextObjective);
                      }
                    }else{
                      $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                    }
                  }else{
                    $player->sendMessage("§cError can't add §e$addingMoney$ §cto your bank account");
                  }
                },
              ));
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BankMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(54, 0, 32)->setCustomName("§r §eDeposit Half Of Your Money §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(323, 0, 1)->setCustomName("§r §eDeposit Custom Amount Of Your Money §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(54, 0, 64)->setCustomName("§r §eDeposit All Of Your Money §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function WhitdrawMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        if($itemOutId === 125 && $itemOut->getCount() === 32)
        {
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $playerBankMoney = $this->api->getBankMoney($player);
            $addingMoney = $playerBankMoney/2;
            if($addingMoney >= 1)
            {
              $economy->addToPlayerBalance($player->getName(), $addingMoney);
              $this->api->reduceBankMoney($player, $addingMoney);
              $player->sendMessage("§aSuccessfully whitdrawed §e$addingMoney$ §afrom your bank account");
            }else{
              $player->sendMessage("§cError can't whitdraw §e$addingMoney$ §cfrom your bank account");
            }
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 323 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
          $this->source->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function () use ($player): void 
            {
              $this->source->getInstance()->getUI()->CustomWhitdrawMenu($player);
            }
          ), 8);
        }elseif($itemOutId === 125 && $itemOut->getCount() === 64)
        {
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          if($economy !== null)
          {
            $playerBankMoney = $this->api->getBankMoney($player);
            $addingMoney = $playerBankMoney;
            if($addingMoney >= 1)
            {
              $economy->addToPlayerBalance($player->getName(), $addingMoney);
              $this->api->reduceBankMoney($player, $addingMoney);
              $player->sendMessage("§aSuccessfully whitdrawed §e$addingMoney$ §afrom your bank account");
            }else{
              $player->sendMessage("§cError can't whitdraw §e$addingMoney$ §cfrom your bank account");
            }
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BankMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(125, 0, 32)->setCustomName("§r §eWhitdraw Half From Your Bank Account §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(323, 0, 1)->setCustomName("§r §eWhitdraw Custom Amount From Your Bank Account §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(125, 0, 64)->setCustomName("§r §eWhitdraw All From Your Bank Account §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function LoanMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        if($itemOutId === 351 && $itemOutMeta === 11)
        {
          $player->removeCurrentWindow();
          $this->source->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function () use ($player): void 
            {
              $this->source->getInstance()->getUI()->AquireLoanMenu($player);
            }
          ), 8);
        }elseif($itemOutId === 266 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
          $this->source->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function () use ($player): void 
            {
              $this->source->getInstance()->getUI()->PayLoanMenu($player);
            }
          ), 8);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BankMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $merit = $this->api->getLoanMerit($player);
    $loan = $this->api->getLoan($player);
    $time = $this->api->changeNumericFormat(($this->source->getInstance()->getPlayerFile($player)->getNested("Bank.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Bank.Time")), "time");
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(351, 11, 1)->setCustomName("§r §aAquire Loan §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(339, 0, 1)->setCustomName("§r §eInfo §r\n§r §aMerit§7: §e$merit §r\n§r §aCurrent Loan§7: §e$loan §r\n§r §aRemaining Time§7: §e$time §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r §aPay Loan §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function FriendsMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 397 && $itemOutMeta === 3)
        {
          $friendName = str_replace(["§r §b", " §r"], ["", ""], $itemOutName);
          $this->ManageFriendMenu($player, $friendName);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $i = 0;
    $playerName = $player->getName();
    if(count($this->source->getInstance()->getPlayerFile($playerName)->get("Friends")) >= 1)
    {
      foreach($this->source->getInstance()->getPlayerFile($playerName)->get("Friends") as $friend)
      {
        if($this->api->hasSkyIsland($friend))
        {
          if($i < 5)
          {
            $slot = $i + 11;
            $inv->setItem($slot, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §b$friend §r"));
          }elseif($i < 10)
          {
            $slot = $i + 23;
            $inv->setItem($slot, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §b$friend §r"));
          }
          $i++;
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ManageFriendMenu(Player $player, string $friendName)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($friendName) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 397 && $itemOutMeta === 3)
        {
          $this->FriendProfileMenu($player, $friendName);
        }elseif($itemOutId === 2 && $itemOutMeta === 0)
        {
          $worldName = $this->source->getInstance()->getPlayerFile($friendName)->get("Island");
          Server::getInstance()->getWorldManager()->loadWorld($worldName);
          $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
          if(!$this->api->isLocked($friendName))
          {
            $player->teleport($world->getSafeSpawn());
            $player->sendMessage("§aVisiting §e$friendName");
          }elseif($this->source->getInstance()->getPlayerFile($friendName)->getNested("IslandSettings.FriendsVisit"))
          {
            $player->teleport($world->getSpawnLocation());
            $player->sendMessage("§aVisiting §e$friendName");
          }else{
            $player->sendMessage("§cisland locked");
          }
        }elseif($itemOutId === 368 && $itemOutMeta === 0)
        {
          $friend = Server::getInstance()->getPlayerExact($friendName);
          if($friend instanceof Player)
          {
            $player->teleport($friend->getLocation());
            $player->sendMessage("§ateleported to §e$friendName");
          }else{
            $player->sendMessage("§e$friendName §cis offline");
          }
        }elseif($itemOutId === 152 && $itemOutMeta === 0)
        {
          $playerName = $player->getName();
          if($this->api->removeFriend($player, $friendName))
          {
            $player->sendMessage("§asuccessfully removed §e$friendName §afrom your friend list");
          }else{
            $player->sendMessage("§cError can't remove friend");
          }
          $friend = Server::getInstance()->getPlayerExact($friendName);
          if($friend instanceof Player)
          {
            $friend->sendMessage("§cyou are no longer §e$playerName's §cfriend");
          }
          $player->removeCurrentWindow();
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->FriendsMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(2, 0, 1)->setCustomName("§r §aVisit §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §b$friendName's §3Profile §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(368, 0, 1)->setCustomName("§r §eTeleport §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(152, 0, 1)->setCustomName("§r §cRemove §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function FriendProfileMenu(Player $player, string $friendName)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§b$friendName's §3Profile");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($friendName) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ManageFriendMenu($player, $friendName);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
    $inv = $menu->getInventory();
    $economy->getPlayerBalance($player->getName(),
      ClosureContext::create(
        function (?int $balance) use($player, $friendName, $inv, $menu): void
        {
          if(!is_null($balance))
          {
            $rank = Ranks::getInstance()->getRankOfPlayer($friendName);
            $pet = $this->source->getInstance()->getPlayerFile($friendName)->getNested("Pet.Current");
            $minerLevel = $this->source->getInstance()->getPlayerFile($friendName)->getNested("Level.Miner");
            $islandLevel = $this->source->getInstance()->getPlayerFile($friendName)->getNested("Level.IslandLevel");
            $farmerLevel = $this->source->getInstance()->getPlayerFile($friendName)->getNested("Level.Farmer");
            $lumberjackLevel = $this->source->getInstance()->getPlayerFile($friendName)->getNested("Level.Lumberjack");
            $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(11, ItemFactory::getInstance()->get(310, 0, 1)->setCustomName("§r §aRank§7: $rank §r"));
            $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(13, ItemFactory::getInstance()->get(296, 0, 1)->setCustomName("§r §eFarmer Level§7: §b$farmerLevel §r"));
            $inv->setItem(14, ItemFactory::getInstance()->get(129, 0, 1)->setCustomName("§r §1Miner Level§7: §b$minerLevel §r"));
            $inv->setItem(15, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r §aLumberjack Level§7: §b$lumberjackLevel §r"));
            $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(20, ItemFactory::getInstance()->get(383, 0, 1)->setCustomName("§r §ePets§7: §b$pet §r"));
            $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(29, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r §eBalance§7: §b".$balance." §r"));
            $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(32, ItemFactory::getInstance()->get(2, 0, 1)->setCustomName("§r §aIsland Level§7: §b$islandLevel §r"));
            $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
            $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
            $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
            $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
            if($this->Window !== "Double-Chest")
            {
              $menu->send($player);
              $this->Window = "Double-Chest";
            }
          }
        },
      ));
  }
  
  public function BazaarMenu(Player $player)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Bazaar");
    $menu->setListener(
      function (InvMenuTransaction $transaction): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 278 && $itemOutMeta === 0 && $itemOutName === "§r §eMining §r")
        {
          $this->api->bazaar($inv, "Mining");
        }elseif($itemOutId === 346 && $itemOutMeta === 0 && $itemOutName === "§r §eFishing §r")
        {
          $this->api->bazaar($inv, "Fishing");
        }elseif($itemOutId === 296 && $itemOutMeta === 0 && $itemOutName === "§r §eFarming §r")
        {
          $this->api->bazaar($inv, "Farming");
        }elseif($itemOutId === 17 && $itemOutMeta === 0 && $itemOutName === "§r §eLumberjack §r")
        {
          $this->api->bazaar($inv, "Lumberjack");
        }elseif($itemOutId === 98 && $itemOutMeta === 0 && $itemOutName === "§r §eBlocks §r")
        {
          $this->api->bazaar($inv, "Blocks-1");
        }elseif($itemOutId === 264 && $itemOutMeta === 0 && $itemOutName === "§r §eCustom Items §r")
        {
          $this->api->bazaar($inv, "Custom-Items");
        }elseif($itemOutId === 340 && $itemOutMeta === 0 && $itemOutName === "§r §eManage Orders §r")
        {
          $this->ManageOrderMenu($player);
        }elseif($itemOutId === 340 && $itemOutMeta === 0 && $itemOutName === "§r §eManage Offers §r")
        {
          $this->ManageOfferMenu($player);
        }elseif($itemOutId === 160 && ($itemOutName === "§r §ePrivious Page §r" || $itemOutName === "§r §aNext Page §r"))
        {
          if($itemOutMeta === 4)
          {
            $this->api->bazaar($inv, $itemOut->getNamedTag()->getString("PriviousPage"));
          }elseif($itemOutMeta === 5)
          {
            $this->api->bazaar($inv, $itemOut->getNamedTag()->getString("NextPage"));
          }
        }elseif($itemOutName !== "§r §7 §r")
        {
          $this->BazaarOrderMenu($player, $itemOut);
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eMining §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(346, 0, 1)->setCustomName("§r §eFishing §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(296, 0, 1)->setCustomName("§r §eFarming §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r §eLumberjack §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(98, 0, 1)->setCustomName("§r §eBlocks §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(264, 0, 1)->setCustomName("§r §eCustom Items §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(340, 0, 1)->setCustomName("§r §eManage Orders §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(340, 0, 1)->setCustomName("§r §eManage Offers §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $this->api->bazaar($inv, "Mining");
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function BazaarOrderMenu(Player $player, Item $item)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bBazaar");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use($item): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 54 && $itemOutMeta === 0)
        {
          $demand = $this->api->getCheapestOffer($item, $player);
          if($demand !== PHP_INT_MAX)
          {
            $this->BazaarItemCountMenu($player, $item, "Order", $demand);
          }else{
            $player->sendMessage("§cno offer available");
          }
        }elseif($itemOutId === 410 && $itemOutMeta === 0)
        {
          $demand = $this->api->getExpensiveOrder($item, $player);
          if($demand !== 0)
          {
            $this->BazaarItemCountMenu($player, $item, "Offer", $demand);
          }else{
            $player->sendMessage("§cno order available");
          }
        }elseif($itemOutId === 339 && $itemOutMeta === 0)
        {
          $this->BazaarItemCountMenu($player, $item, "Order");
        }elseif($itemOutId === 395 && $itemOutMeta === 0)
        {
          $this->BazaarItemCountMenu($player, $item, "Offer");
        }elseif($itemOutId === 262 && $itemOutMeta === 0 && $itemOutName === "§r §cBack §r\n§r §7click to go back to the privious menu §r")
        {
          $this->BazaarMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0 && $itemOutName === "§r §cExit §r\n§r §7click to exit the menu §r")
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $demand = $this->api->getCheapestOffer($item, $player);
    if($demand === PHP_INT_MAX)
    {
      $demand = "NO OFFERS";
    }
    $inv->setItem(19, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eInstantly Buy §r\n§r §7 §r\n§r §7Demanded: §e$demand §r"));
    $demand = $this->api->getExpensiveOrder($item, $player);
    if($demand === 0)
    {
      $demand = "NO ORDERS";
    }
    $inv->setItem(20, ItemFactory::getInstance()->get(410, 0, 1)->setCustomName("§r §eInstantly Sell §r\n§r §7 §r\n§r §7Demanded: §e$demand §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, $item);
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $orderRanking = $this->api->getOrdersRanking($item);
    $inv->setItem(24, ItemFactory::getInstance()->get(339, 0, 1)->setCustomName("§r §eCreate Order §r\n§r §7 §r\n§r §aTop Orders §r\n$orderRanking"));
    $offerRanking = $this->api->getOffersRanking($item);
    $inv->setItem(25, ItemFactory::getInstance()->get(395, 0, 1)->setCustomName("§r §eCreate Offer §r\n§r §7 §r\n§r §aTop Offers §r\n$offerRanking"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function BazaarItemCountMenu(Player $player, Item $item, string $type, int $demand = -1)
  {
    $menu = $this->SingleChest;
    $menu->setName("§bBazaar §3Count");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use($item, $type, $demand): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === $item->getId() && $itemOutMeta === $item->getMeta() && $itemOutName !== "§r §7 §r")
        {
          if($type === "Order")
          {
            if($demand === -1)
            {
              $item->setCount($itemOut->getCount());
              $this->OrderMenu($player, $item);
            }else{
              $orders = 0;
              foreach($this->api->getOrders() as $order)
              {
                $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/orders/$order", Config::YAML, [
                  ]); 
                $customer = $file->getNested("Order.Customer");
                if($customer === $player->getName())
                {
                  $orders++;
                }
              }
              $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
              $economy->getPlayerBalance($player->getName(),
                ClosureContext::create(
                  function (?int $balance) use($player, $itemOut, $economy, $item, $demand, $orders): void
                  {
                    if(is_numeric($balance))
                    {
                      $item->setCount($itemOut->getCount());
                      if($balance >= ($demand * $item->getCount()))
                      {
                        if($orders < 5)
                        {
                          $economy->subtractFromPlayerBalance($player->getName(), $demand * $item->getCount());
                          $this->api->createOrder($player->getName(), $item, $demand);
                          $player->sendMessage("§aorder created §e{$item->getName()} §afor §e$demand per unit");
                        }else{
                          $player->sendMessage("§cyou can't create more than 5 orders");
                        }
                      }else{
                        $player->sendMessage("§cyou don't have enough money");
                      }
                    }else{
                      $player->sendMessage("§cyou don't have enough money");
                    }
                  },
                ));
              $player->removeCurrentWindow();
            }
          }elseif($type === "Offer")
          {
            if($demand === -1)
            {
              if($this->api->hasItem($item->setCount($itemOut->getCount()), $player->getInventory(), true))
              {
                $item->setCount($itemOut->getCount());
                $this->OfferMenu($player, $item);
              }else{
                $player->sendMessage("§cyou don't have that much items in your inventory");
              }
            }else{
              $offers = 0;
              foreach($this->api->getOffers() as $offer)
              {
                $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/offers/$offer", Config::YAML, [
                  ]); 
                $seller = $file->getNested("Offer.Seller");
                if($seller === $player->getName())
                {
                  $offers++;
                }
              }
              if($this->api->hasItem($item->setCount($itemOut->getCount()), $player->getInventory(), true))
              {
                if($offers < 5)
                {
                  $this->api->removeItem($player->getInventory(), true, $item);
                  $this->api->createOffer($player->getName(), $item, $demand);
                  $player->sendMessage("§aoffer created §e{$item->getName()} §afor §e$demand per unit");
                }else{
                  $player->sendMessage("§cyou can't create more than 5 offers");
                }
              }else{
                $player->sendMessage("§cyou don't have that much items in your inventory");
              }
              $player->removeCurrentWindow();
            }
          }
        }elseif($itemOutId === 54 && $itemOutMeta === 0 && $itemOutName === "§r §eWhole-Inventory §r")
        {
          if($type === "Order")
          {
            $slots = 0;
            $playerInventory = $player->getInventory();
            for($i = 0; $i <= ($playerInventory->getSize() - 1); $i++)
            {
              if($playerInventory->isSlotEmpty($i))
              {
                $slots++;
              }
            }
            $count = $slots * $item->getMaxStackSize();
            if($count > 0)
            {
              if($demand === -1)
              {
                $item->setCount($count);
                $this->OrderMenu($player, $item);
              }else{
                $orders = 0;
                foreach($this->api->getOrders() as $order)
                {
                  $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/orders/$order", Config::YAML, [
                    ]); 
                  $customer = $file->getNested("Order.Customer");
                  if($customer === $player->getName())
                  {
                    $orders++;
                  }
                }
                $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
                $economy->getPlayerBalance($player->getName(),
                  ClosureContext::create(
                    function (?int $balance) use($player, $economy, $item, $demand, $orders, $count): void
                    {
                      if(is_numeric($balance))
                      {
                        $item->setCount($count);
                        if($balance >= ($demand * $count))
                        {
                          if($orders < 5)
                          {
                            $economy->subtractFromPlayerBalance($player->getName(), $demand * $count);
                            $this->api->createOrder($player->getName(), $item, $demand);
                            $player->sendMessage("§aorder created §e{$item->getName()} §afor §e$demand per unit");
                          }else{
                            $player->sendMessage("§cyou can't create more than 5 orders");
                          }
                        }else{
                          $player->sendMessage("§cyou don't have enough money");
                        }
                      }else{
                        $player->sendMessage("§cyou don't have enough money");
                      }
                    },
                  ));
                $player->removeCurrentWindow();
              }
            }
          }elseif($type === "Offer")
          {
            $slots = 0;
            $playerInventory = $player->getInventory();
            for($i = 0; $i <= ($playerInventory->getSize() - 1); $i++)
            {
              $b_item = $playerInventory->getItem($i);
              if($item->getId() === $b_item->getId() && $item->getMeta() === $b_item->getMeta() && $item->getName() === $b_item->getName())
              {
                $slots++;
              }
            }
            $count = $slots * $item->getMaxStackSize();
            if($count > 0)
            {
              if($demand === -1)
              {
                if($this->api->hasItem($item->setCount($count), $player->getInventory(), true))
                {
                  $item->setCount($count);
                  $this->OfferMenu($player, $item);
                }else{
                  $player->sendMessage("§cyou don't have that much items in your inventory");
                }
              }else{
                $offers = 0;
                foreach($this->api->getOffers() as $offer)
                {
                  $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/offers/$offer", Config::YAML, [
                    ]); 
                  $seller = $file->getNested("Offer.Seller");
                  if($seller === $player->getName())
                  {
                    $offers++;
                  }
                }
                if($this->api->hasItem($item->setCount($count), $player->getInventory(), true))
                {
                  if($offers < 5)
                  {
                    $this->api->removeItem($player->getInventory(), true, $item);
                    $this->api->createOffer($player->getName(), $item, $demand);
                    $player->sendMessage("§aoffer created §e{$item->getName()} §afor §e$demand per unit");
                  }else{
                    $player->sendMessage("§cyou can't create more than 5 offers");
                  }
                }else{
                  $player->sendMessage("§cyou don't have that much items in your inventory");
                }
                $player->removeCurrentWindow();
              }
            }
          }
        }elseif($itemOutId === 323 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
          $this->api->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function() use($player, $item, $type, $demand)
            {
              if($demand === -1)
              {
                $this->api->getSource()->getUI()->ItemCountMenu($player, $item, $type);
              }else{
                $this->api->getSource()->getUI()->InstantItemCountMenu($player, $item, $demand, $type);
              }
            }
          ), 10);
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $a_item = clone $item;
    $inv->setItem(10, $a_item->setCount(1)->setCustomName($a_item->getCustomName() . "\n§r §7 §r\n§r §7Count: §e1x"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $b_item = clone $item;
    $inv->setItem(12, $b_item->setCount($b_item->getMaxStackSize())->setCustomName($b_item->getCustomName() . "\n§r §7 §r\n§r §7Count: §e" . $b_item->getMaxStackSize() . "x"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eWhole-Inventory §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(323, 0, 1)->setCustomName("§r §eCustom §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Single-Chest")
    {
      $menu->send($player);
      $this->Window = "Single-Chest";
    }
  }
  
  public function OrderMenu(Player $player, Item $item)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bBazaar §3Order");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use($item): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        $orders = 0;
        foreach($this->api->getOrders() as $order)
        {
          $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/orders/$order", Config::YAML, [
            ]); 
          $customer = $file->getNested("Order.Customer");
          if($customer === $player->getName())
          {
            $orders++;
          }
        }
        
        if($itemOutId === 371 && $itemOutMeta === 0)
        {
          $top = $this->api->getExpensiveOrder($item, $player);
          if($top !== 0)
          {
            $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
            $economy->getPlayerBalance($player->getName(),
            ClosureContext::create(
              function (?int $balance) use($player, $economy, $item, $top, $orders): void
              {
                if(is_numeric($balance))
                {
                  if($balance >= ($top * $item->getCount()))
                  {
                    if($orders < 5)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $top * $item->getCount());
                      $this->api->createOrder($player->getName(), $item, $top);
                      $player->sendMessage("§aorder created §e{$item->getName()} §afor §e$top per unit");
                    }else{
                      $player->sendMessage("§cyou can't create more than 5 orders");
                    }
                  }else{
                    $player->sendMessage("§cyou don't have enough money");
                  }
                }else{
                  $player->sendMessage("§cyou don't have enough money");
                }
              },
            ));
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 266 && $itemOutMeta === 0)
        {
          $top = $this->api->getExpensiveOrder($item, $player);
          if($top !== 0 && $top + 1 <= 10000000)
          {
            $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
            $economy->getPlayerBalance($player->getName(),
            ClosureContext::create(
              function (?int $balance) use($player, $economy, $item, $top, $orders): void
              {
                if(is_numeric($balance))
                {
                  $top++;
                  if($balance >= ($top * $item->getCount()))
                  {
                    if($orders < 5)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $top * $item->getCount());
                      $this->api->createOrder($player->getName(), $item, $top);
                      $player->sendMessage("§aorder created §e{$item->getName()} §afor §e$top per unit");
                    }else{
                      $player->sendMessage("§cyou can't create more than 5 orders");
                    }
                  }else{
                    $player->sendMessage("§cyou don't have enough money");
                  }
                }else{
                  $player->sendMessage("§cyou don't have enough money");
                }
              },
            ));
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 41 && $itemOutMeta === 0)
        {
          $demand = $this->api->getCheapestOffer($item, $player);
          if($demand !== PHP_INT_MAX)
          {
            $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
            $economy->getPlayerBalance($player->getName(),
            ClosureContext::create(
              function (?int $balance) use($player, $economy, $item, $demand, $orders): void
              {
                if(is_numeric($balance))
                {
                  if($balance >= ($demand * $item->getCount()))
                  {
                    if($orders < 5)
                    {
                      $economy->subtractFromPlayerBalance($player->getName(), $demand * $item->getCount());
                      $this->api->createOrder($player->getName(), $item, $demand);
                      $player->sendMessage("§aorder created §e{$item->getName()} §afor §e$demand per unit");
                    }else{
                      $player->sendMessage("§cyou can't create more than 5 orders");
                    }
                  }else{
                    $player->sendMessage("§cyou don't have enough money");
                  }
                }else{
                  $player->sendMessage("§cyou don't have enough money");
                }
              },
            ));
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 323 && $itemOutMeta === 0 && $itemOutName === "§r §eCustom §r")
        {
          $player->removeCurrentWindow();
          $this->api->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function() use($player, $item)
            {
              $this->api->getSource()->getUI()->CustomBazaarPrice($player, $item, "Order");
            }
          ), 10);
        }elseif($itemOutId === 262 && $itemOutMeta === 0 && $itemOutName === "§r §cBack §r\n§r §7click to go back to the privious menu §r")
        {
          $this->BazaarOrderMenu($player, $item);
        }elseif($itemOutId === 331 && $itemOutMeta === 0 && $itemOutName === "§r §cExit §r\n§r §7click to exit the menu §r")
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory(); 
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $top = $this->api->getExpensiveOrder($item, $player);
    if($top === 0)
    {
      $inv->setItem(19, ItemFactory::getInstance()->get(371, 0, 1)->setCustomName("§r §eSame As The Top §r\n§r §7 §r\n§r §6Per Unit: §eNO ORDERS §r"));
    }else{
      $inv->setItem(19, ItemFactory::getInstance()->get(371, 0, 1)->setCustomName("§r §eSame As The Top §r\n§r §7 §r\n§r §6Per Unit: §e$top §r\n§r §7 §r\n§r §6Total: §e".$top * $item->getCount()));
    }
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $top = $this->api->getExpensiveOrder($item, $player);
    if($top === 0)
    {
      $inv->setItem(21, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r §eBeat The Top With + 1 §r\n§r §7 §r\n§r §6Per Unit: §eNO ORDERS §r"));
    }else{
      $top++;
      $inv->setItem(21, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r §eBeat The Top With + 1 §r\n§r §7 §r\n§r §6Per Unit: §e".$top." §r\n§r §6Total: §e".$top * $item->getCount()));
    }
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $demand = $this->api->getCheapestOffer($item, $player);
    if($demand === PHP_INT_MAX)
    {
      $demand = "NO OFFERS";
    }
    $inv->setItem(23, ItemFactory::getInstance()->get(41, 0, 1)->setCustomName("§r §eMost Demanded §r\n§r §7 §r\n§r §6Per Unit: §e$demand §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(323, 0, 1)->setCustomName("§r §eCustom §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function OfferMenu(Player $player, Item $item)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bBazaar §3Offer");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use($item): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        $offers = 0;
        foreach($this->api->getOffers() as $offer)
        {
          $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/offers/$offer", Config::YAML, [
            ]); 
          $seller = $file->getNested("Offer.Seller");
          if($seller === $player->getName())
          {
            $offers++;
          }
        }
        
        if($itemOutId === 371 && $itemOutMeta === 0)
        {
          $top = $this->api->getCheapestOffer($item, $player);
          if($top !== PHP_INT_MAX)
          {
            $player->removeCurrentWindow();
            if($this->api->hasItem($item, $player->getInventory(), true))
            {
              if($offers < 5)
              {
                $this->api->removeItem($player->getInventory(), true, $item);
                $this->api->createOffer($player->getName(), $item, $top);
                $player->sendMessage("§aoffer created §e{$item->getName()} §afor §e$top per unit");
              }else{
                $player->sendMessage("§cyou can't create more than 5 offers");
              }
            }else{
              $player->sendMessage("§cyou don't have that much items in your inventory");
            }
          }
          $player->removeCurrentWindow();
        }elseif($itemOutId === 266 && $itemOutMeta === 0)
        {
          $top = $this->api->getCheapestOffer($item, $player);
          if($top !== PHP_INT_MAX && ($top - 1) >= 1)
          {
            $top--;
            $player->removeCurrentWindow();
            if($this->api->hasItem($item, $player->getInventory(), true))
            {
              if($offers < 5)
              {
                $this->api->removeItem($player->getInventory(), true, $item);
                $this->api->createOffer($player->getName(), $item, $top);
                $player->sendMessage("§aoffer created §e{$item->getName()} §afor §e$top per unit");
              }else{
                $player->sendMessage("§cyou can't create more than 5 offers");
              }
            }else{
              $player->sendMessage("§cyou don't have that much items in your inventory");
            }
          }
          $player->removeCurrentWindow();
        }elseif($itemOutId === 41 && $itemOutMeta === 0)
        {
          $demand = $this->api->getExpensiveOrder($item, $player);
          if($demand !== 0)
          {
            if($this->api->hasItem($item, $player->getInventory(), true))
            {
              if($offers < 5)
              {
                $this->api->removeItem($player->getInventory(), true, $item);
                $this->api->createOffer($player->getName(), $item, $demand);
                $player->sendMessage("§aoffer created §e{$item->getName()} §afor §e$demand per unit");
              }else{
                $player->sendMessage("§cyou can't create more than 5 offers");
              }
            }else{
              $player->sendMessage("§cyou don't have that much items in your inventory");
            }
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 323 && $itemOutMeta === 0 && $itemOutName === "§r §eCustom §r")
        {
          $player->removeCurrentWindow();
          $this->api->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function() use($player, $item)
            {
              $this->api->getSource()->getUI()->CustomBazaarPrice($player, $item, "Offer");
            }
          ), 10);
        }elseif($itemOutId === 262 && $itemOutMeta === 0 && $itemOutName === "§r §cBack §r\n§r §7click to go back to the privious menu §r")
        {
          $this->BazaarOrderMenu($player, $item);
        }elseif($itemOutId === 331 && $itemOutMeta === 0 && $itemOutName === "§r §cExit §r\n§r §7click to exit the menu §r")
        {
          $player->removeCurrentWindow();
        }
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory(); 
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $top = $this->api->getCheapestOffer($item, $player);
    if($top === PHP_INT_MAX)
    {
      $inv->setItem(19, ItemFactory::getInstance()->get(371, 0, 1)->setCustomName("§r §eSame As The Top §r\n§r §7 §r\n§r §6Per Unit: §eNO OFFERS §r"));
    }else{
      $inv->setItem(19, ItemFactory::getInstance()->get(371, 0, 1)->setCustomName("§r §eSame As The Top §r\n§r §7 §r\n§r §6Per Unit: §e$top §r\n§r §7 §r\n§r §6Total: §e".$top * $item->getCount()));
    }
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $top = $this->api->getCheapestOffer($item, $player);
    if($top === PHP_INT_MAX)
    {
      $inv->setItem(21, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r §eBeat The Top With - 1 §r\n§r §7 §r\n§r §6Per Unit: §eNO OFFERS §r"));
    }else{
      $top--;
      $inv->setItem(21, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r §eBeat The Top With - 1 §r\n§r §7 §r\n§r §6Per Unit: §e".$top." §r\n§r §6Total: §e".$top * $item->getCount()));
    }
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $demand = $this->api->getExpensiveOrder($item, $player);
    if($demand === 0)
    {
      $demand = "NO ORDERS";
    }
    $inv->setItem(23, ItemFactory::getInstance()->get(41, 0, 1)->setCustomName("§r §eMost Demanded §r\n§r §7 §r\n§r §6Per Unit: §e$demand §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(323, 0, 1)->setCustomName("§r §eCustom §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ManageOrderMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bBazaar §3Manage");
    $menu->setListener(
      function (InvMenuTransaction $transaction): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $action = $transaction->getAction();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0 && $itemOutName === "§r §cBack §r\n§r §7click to go back to the privious menu §r")
        {
          $this->BazaarMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0 && $itemOutName === "§r §cExit §r\n§r §7click to exit the menu §r")
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160 && $itemOutName !== "§r §7 §r" && $itemOutId !== 0)
        {
          $itemOut_nbt = clone $itemOut->getNamedTag();
          $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/orders/" . $itemOut_nbt->getString("File"), Config::YAML, [
            ]);
          $needed = $file->getNested("Order.Item.Count") - $file->getNested("Order.Bought");
          if($needed === 0)
          {
            $slots = 0;
            $id = $file->getNested("Order.Item.Id");
            $playerInventory = $player->getInventory();
            $name = $file->getNested("Order.Item.Name");
            $meta = $file->getNested("Order.Item.Meta");
            $count = $file->getNested("Order.Item.Count");
            $price = $file->getNested("Order.Item.Price");
            $nbt = unserialize($file->getNested("Order.Item.Nbt"));
            $item = ItemFactory::getInstance()->get($id, $meta, $count)->setCustomName($name)->setNamedTag($nbt);
            for($i = 0; $i <= ($playerInventory->getSize() - 1); $i++)
            {
              if($playerInventory->isSlotEmpty($i))
              {
                $slots++;
              }
            }
            $count_2 = $slots * $item->getMaxStackSize();
            
            if($count_2 >= 1)
            {
              if(($count - $count_2) <= 0)
              {
                if($player->getInventory()->canAddItem($item))
                {
                  $inv->setItem($action->getSlot(), ItemFactory::getInstance()->get(0, 0, 0));
                  $player->getInventory()->addItem($item);
                  unlink($this->api->getSource()->getDataFolder() . "bazaar/orders/" . $itemOut_nbt->getString("File"));
                  $player->sendMessage("§aorder claimed");
                }else{
                  $player->sendMessage("§cyour inventory is full");
                }
              }else{
                $total_count = $count - $count_2;
                $item->setCount($total_count);
                if($player->getInventory()->canAddItem($item))
                {
                  $player->getInventory()->addItem($item);
                  $player->sendMessage("§aorder claimed");
                  $file->setNested("Order.Item.Count", $total_count);
                  $file->setNested("Order.bought", $total_count);
                  $file->save();
                  $inv->setItem($action->getSlot(), $itemOut->setCustomName($name."\n§r §7 §r\n§r §7Progress: §e$total_count" . "§7/" . "§e$total_count §r\n§r §7 §r\n§r §7Price Per Unit: §e$price §r\n§r §7 §r\n§r §7Total: §e" . $price * $total_count));
                }else{
                  $player->sendMessage("§cyour inventory is full");
                }
              }
            }else{
              $player->sendMessage("§cyour inventory is full");
            }
          }elseif($needed === $file->getNested("Order.Item.Count"))
          {
            $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
            $economy->addToPlayerBalance($player->getName(), $file->getNested("Order.Price") * $file->getNested("Order.Item.Count"));
            $inv->setItem($action->getSlot(), ItemFactory::getInstance()->get(0, 0, 0));
            unlink($this->api->getSource()->getDataFolder() . "bazaar/orders/" . $itemOut_nbt->getString("File"));
            $player->sendMessage("§corder cancelled");
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $i = 1;
    foreach($this->api->getOrders() as $order)
    {
      $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/orders/$order", Config::YAML, [
        ]);
      $customer = $file->getNested("Order.Customer");
      if($customer === $player->getName())
      {
        $id = $file->getNested("Order.Item.Id");
        $meta = $file->getNested("Order.Item.Meta");
        $count = $file->getNested("Order.Item.Count");
        $name = $file->getNested("Order.Item.Name");
        $bought = $file->getNested("Order.Bought");
        $Price = $file->getNested("Order.Price");
        $nbt = unserialize($file->getNested("Order.Item.Nbt"));
        $item = ItemFactory::getInstance()->get($id, $meta, 1)->setNamedTag($nbt)->setCustomName($name."\n§r §7 §r\n§r §7Progress: §e$bought" . "§7/" . "§e$count §r\n§r §7 §r\n§r §7Price Per Unit: §e$Price §r\n§r §7 §r\n§r §7Total: §e" . $Price * $count);
        $nbt = clone $item->getNamedTag();
        $nbt->setString("File", $order);
        $item->setNamedTag($nbt);
        $slot = $i + 19;
        $inv->setItem($slot, $item);
        $i++;
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ManageOfferMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bBazaar §3Manage");
    $menu->setListener(
      function (InvMenuTransaction $transaction): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $action = $transaction->getAction();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0 && $itemOutName === "§r §cBack §r\n§r §7click to go back to the privious menu §r")
        {
          $this->BazaarMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0 && $itemOutName === "§r §cExit §r\n§r §7click to exit the menu §r")
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160 && $itemOutName !== "§r §7 §r" && $itemOutId !== 0)
        {
          $itemOut_nbt = clone $itemOut->getNamedTag();
          $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/offers/" . $itemOut_nbt->getString("File"), Config::YAML, [
            ]);
          $id = $file->getNested("Offer.Item.Id");
          $meta = $file->getNested("Offer.Item.Meta");
          $count = $file->getNested("Offer.Item.Count") - $file->getNested("Offer.Sold");
          $name = $file->getNested("Offer.Item.Name");
          $nbt = unserialize($file->getNested("Offer.Item.Nbt"));
          $item = ItemFactory::getInstance()->get($id, $meta, $count)->setNamedTag($nbt)->setCustomName($name);
          if($player->getInventory()->canAddItem($item))
          {
            $inv->setItem($action->getSlot(), ItemFactory::getInstance()->get(0, 0, 0));
            $player->getInventory()->addItem($item);
            unlink($this->api->getSource()->getDataFolder() . "bazaar/offers/" . $itemOut_nbt->getString("File"));
            $player->sendMessage("§coffer cancelled");
          }else{
            $player->sendMessage("§cyour inventory is full");
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $i = 1;
    foreach($this->api->getOffers() as $offer)
    {
      $file = new Config($this->api->getSource()->getDataFolder() . "bazaar/offers/$offer", Config::YAML, [
        ]);
      $seller = $file->getNested("Offer.Seller");
      if($seller === $player->getName())
      {
        $id = $file->getNested("Offer.Item.Id");
        $meta = $file->getNested("Offer.Item.Meta");
        $count = $file->getNested("Offer.Item.Count");
        $name = $file->getNested("Offer.Item.Name");
        $sold = $file->getNested("Offer.Sold");
        $Price = $file->getNested("Offer.Price");
        $nbt = unserialize($file->getNested("Offer.Item.Nbt"));
        $item = ItemFactory::getInstance()->get($id, $meta, 1)->setNamedTag($nbt)->setCustomName($name."\n§r §7 §r\n§r §7Progress: §e$sold" . "§7/" . "§e$count §r\n§r §7 §r\n§r §7Price Per Unit: §e$Price §r\n§r §7 §r\n§r §7Total: §e" . $Price * $count);
        $nbt = clone $item->getNamedTag();
        $nbt->setString("File", $offer);
        $item->setNamedTag($nbt);
        $slot = $i + 19;
        $inv->setItem($slot, $item);
        $i++;
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function VisitMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 397 && $itemOutMeta === 3)
        {
          $visitingPlayer = Server::getInstance()->getPlayerExact(str_replace(["§r §b", " §r"], ["", ""], $itemOut->getCustomName()));
          if(!is_null($visitingPlayer))
          {
          $visitingPlayerName = $visitingPlayer->getName();
          if($visitingPlayer instanceof Player)
          {
            if($this->api->hasSkyIsland($visitingPlayerName))
            {
              $worldName = $this->source->getInstance()->getPlayerFile($visitingPlayerName)->get("Island");
              if(!is_null($worldName))
              {
                Server::getInstance()->getWorldManager()->loadWorld($worldName);
                $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
                if($world !== null)
                {
                  if($world->getFolderName() !== $player->getLocation()->world->getFolderName())
                  {
                    if(!$this->source->getInstance()->getPlayerFile($visitingPlayerName)->getNested("IslandSettings.Locked"))
                    {
                      if(count($world->getPlayers()) < $this->source->getInstance()->getPlayerFile($visitingPlayerName)->getNested("IslandSettings.MaxVisitors"))
                      {
                        $player->teleport($world->getSpawnLocation());
                        $player->sendMessage("§aVisiting §e$visitingPlayerName");
                      }else{
                        $player->sendMessage("§cmaximum number of visitors reached");
                      }
                    }elseif($this->source->getInstance()->getPlayerFile($visitingPlayerName)->getNested("IslandSettings.FriendsVisit"))
                     {
                      $isFriend = false;
                      if(count($this->source->getInstance()->getPlayerFile($visitingPlayerName)->get("Friends")) >= 1)
                      {
                        foreach($this->source->getInstance()->getPlayerFile($visitingPlayerName)->get("Friends") as $friend)
                        {
                          if($friend === $playerName)
                          {
                            $isFriend = true;
                             break;
                          }
                        }
                      }
                      if($isFriend)
                      {
                        if(count($world->getPlayers()) < $this->source->getInstance()->getPlayerFile($visitingPlayerName)->getNested("IslandSettings.MaxVisitors"))
                        {
                          $player->teleport($world->getSpawnLocation());
                          $player->sendMessage("§aVisiting §e$visitingPlayerName");
                        }else{
                          $player->sendMessage("§cMaximum number of visitor reached");
                        }
                      }else{
                        $player->sendMessage("§cIsland is locked");
                      }
                    }else{
                      $player->sendMessage("§cIsland is locked");
                    }
                  }else{
                    $player->sendMessage("§can error occurred");
                  }
                }else{
                  $player->sendMessage("§can error occurred");
                }
              }
            }else{
              $player->sendMessage("§can error occurred");
            }
          }
          }
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $i = 0;
    foreach(Server::getInstance()->getOnlinePlayers() as $online)
    {
      if($player->getName() !== $online->getName())
      {
        if($i < 7)
        {
          $slot = $i + 10;
          $playerName = $online->getName();
          $inv->setItem($slot, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §b$playerName §r"));
        }elseif($i < 14)
        {
          $slot = $i + 12;
          $playerName = $online->getName();
          $inv->setItem($slot, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §b$playerName §r"));
        }elseif($i < 21)
        {
          $slot = $i + 14;
          $playerName = $online->getName();
          $inv->setItem($slot, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §b$playerName §r"));
        }
        $i++;
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function PotionBagMenu(Player $player): void
  {
    $menu = $this->SingleChest;
    $menu->setName("§3PotionBag");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemInId === 373 || $itemInId === 438 || $itemOutId === 373 || $itemOutId === 438)
        {
          return $transaction->continue();
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    if($this->api->getLevel($player, "Lumberjack") >= 2 && $this->api->getLevel($player, "Lumberjack") < 6)
    {
      $menu->setInventoryCloseListener(
        function (Player $player, $inv): void
        {
          $item1 = $inv->getItem(10)->getId().":".$inv->getItem(10)->getMeta().":".$inv->getItem(10)->getCustomName();
          $item2 = $inv->getItem(11)->getId().":".$inv->getItem(11)->getMeta().":".$inv->getItem(11)->getCustomName();
          $item3 = $inv->getItem(12)->getId().":".$inv->getItem(12)->getMeta().":".$inv->getItem(12)->getCustomName();
          $item4 = $inv->getItem(13)->getId().":".$inv->getItem(13)->getMeta().":".$inv->getItem(13)->getCustomName();
          $item5 = $inv->getItem(14)->getId().":".$inv->getItem(14)->getMeta().":".$inv->getItem(14)->getCustomName();
          $item6 = $inv->getItem(15)->getId().":".$inv->getItem(15)->getMeta().":".$inv->getItem(15)->getCustomName();
          $item7 = $inv->getItem(16)->getId().":".$inv->getItem(16)->getMeta().":".$inv->getItem(16)->getCustomName();
          $data = [$item1, $item2, $item3, $item4, $item5, $item6, $item7];
          $this->api->setPotionBag($player, $data);
        }
      );
      $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(18, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
      $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $data = $this->api->getPotionBag($player);
      $item1 = explode(":", $data[0]);
      $item2 = explode(":", $data[1]);
      $item3 = explode(":", $data[2]);
      $item4 = explode(":", $data[3]);
      $item5 = explode(":", $data[4]);
      $item6 = explode(":", $data[5]);
      $item7 = explode(":", $data[6]);
      $item1Id = (int)$item1[0];
      $item2Id = (int)$item2[0];
      $item3Id = (int)$item3[0];
      $item4Id = (int)$item4[0];
      $item5Id = (int)$item5[0];
      $item6Id = (int)$item6[0];
      $item7Id = (int)$item7[0];
      $item1Meta = (int)$item1[1];
      $item2Meta = (int)$item2[1];
      $item3Meta = (int)$item3[1];
      $item4Meta = (int)$item4[1];
      $item5Meta = (int)$item5[1];
      $item6Meta = (int)$item6[1];
      $item7Meta = (int)$item6[1];
      $item1Name = (string)$item1[2];
      $item2Name = (string)$item2[2];
      $item3Name = (string)$item3[2];
      $item4Name = (string)$item4[2];
      $item5Name = (string)$item5[2];
      $item6Name = (string)$item6[2];
      $item7Name = (string)$item7[2];
      $inv->setItem(10, ItemFactory::getInstance()->get($item1Id, $item1Meta, 1)->setCustomName($item1Name));
      $inv->setItem(11, ItemFactory::getInstance()->get($item2Id, $item2Meta, 1)->setCustomName($item2Name));
      $inv->setItem(12, ItemFactory::getInstance()->get($item3Id, $item3Meta, 1)->setCustomName($item3Name));
      $inv->setItem(13, ItemFactory::getInstance()->get($item4Id, $item4Meta, 1)->setCustomName($item4Name));
      $inv->setItem(14, ItemFactory::getInstance()->get($item5Id, $item5Meta, 1)->setCustomName($item5Name));
      $inv->setItem(15, ItemFactory::getInstance()->get($item6Id, $item6Meta, 1)->setCustomName($item6Name));
      $inv->setItem(16, ItemFactory::getInstance()->get($item7Id, $item7Meta, 1)->setCustomName($item7Name));
    }elseif($this->api->getLevel($player, "Lumberjack") >= 6)
    {
      $menu->setInventoryCloseListener(
        function (Player $player, $inv): void
        {
          $item1 = $inv->getItem(0)->getId().":".$inv->getItem(0)->getMeta().":".$inv->getItem(0)->getCustomName();
          $item2 = $inv->getItem(1)->getId().":".$inv->getItem(1)->getMeta().":".$inv->getItem(1)->getCustomName();
          $item3 = $inv->getItem(2)->getId().":".$inv->getItem(2)->getMeta().":".$inv->getItem(2)->getCustomName();
          $item4 = $inv->getItem(3)->getId().":".$inv->getItem(3)->getMeta().":".$inv->getItem(3)->getCustomName();
          $item5 = $inv->getItem(4)->getId().":".$inv->getItem(4)->getMeta().":".$inv->getItem(4)->getCustomName();
          $item6 = $inv->getItem(5)->getId().":".$inv->getItem(5)->getMeta().":".$inv->getItem(5)->getCustomName();
          $item7 = $inv->getItem(6)->getId().":".$inv->getItem(6)->getMeta().":".$inv->getItem(6)->getCustomName();
          $item8 = $inv->getItem(7)->getId().":".$inv->getItem(7)->getMeta().":".$inv->getItem(7)->getCustomName();
          $item9 = $inv->getItem(8)->getId().":".$inv->getItem(8)->getMeta().":".$inv->getItem(8)->getCustomName();
          $item10 = $inv->getItem(9)->getId().":".$inv->getItem(9)->getMeta().":".$inv->getItem(9)->getCustomName();
          $item11 = $inv->getItem(10)->getId().":".$inv->getItem(10)->getMeta().":".$inv->getItem(10)->getCustomName();
          $item12 = $inv->getItem(11)->getId().":".$inv->getItem(11)->getMeta().":".$inv->getItem(11)->getCustomName();
          $item13 = $inv->getItem(12)->getId().":".$inv->getItem(12)->getMeta().":".$inv->getItem(12)->getCustomName();
          $item14 = $inv->getItem(13)->getId().":".$inv->getItem(13)->getMeta().":".$inv->getItem(13)->getCustomName();
          $item15 = $inv->getItem(14)->getId().":".$inv->getItem(14)->getMeta().":".$inv->getItem(14)->getCustomName();
          $item16 = $inv->getItem(15)->getId().":".$inv->getItem(15)->getMeta().":".$inv->getItem(15)->getCustomName();
          $item17 = $inv->getItem(16)->getId().":".$inv->getItem(16)->getMeta().":".$inv->getItem(16)->getCustomName();
          $item18 = $inv->getItem(17)->getId().":".$inv->getItem(17)->getMeta().":".$inv->getItem(17)->getCustomName();
          $data = [$item1, $item2, $item3, $item4, $item5, $item6, $item7, $item8, $item9, $item10, $item11, $item12, $item13, $item14, $item15, $item16, $item17, $item18];
          $this->api->setPotionBag($player, $data);
        }
      );
      $inv->setItem(18, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
      $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
      $data = $this->api->getPotionBag($player);
      $item1 = explode(":", $data[0]);
      $item2 = explode(":", $data[1]);
      $item3 = explode(":", $data[2]);
      $item4 = explode(":", $data[3]);
      $item5 = explode(":", $data[4]);
      $item6 = explode(":", $data[5]);
      $item7 = explode(":", $data[6]);
      $item8 = explode(":", $data[7]);
      $item9 = explode(":", $data[8]);
      $item10 = explode(":", $data[9]);
      $item11 = explode(":", $data[10]);
      $item12 = explode(":", $data[11]);
      $item13 = explode(":", $data[12]);
      $item14 = explode(":", $data[13]);
      $item15 = explode(":", $data[14]);
      $item16 = explode(":", $data[15]);
      $item17 = explode(":", $data[16]);
      $item18 = explode(":", $data[17]);
      $item1Id = (int)$item1[0];
      $item2Id = (int)$item2[0];
      $item3Id = (int)$item3[0];
      $item4Id = (int)$item4[0];
      $item5Id = (int)$item5[0];
      $item6Id = (int)$item6[0];
      $item7Id = (int)$item7[0];
      $item8Id = (int)$item8[0];
      $item9Id = (int)$item9[0];
      $item10Id = (int)$item10[0];
      $item11Id = (int)$item11[0];
      $item12Id = (int)$item12[0];
      $item13Id = (int)$item13[0];
      $item14Id = (int)$item14[0];
      $item15Id = (int)$item15[0];
      $item16Id = (int)$item16[0];
      $item17Id = (int)$item17[0];
      $item18Id = (int)$item18[0];
      $item1Meta = (int)$item1[1];
      $item2Meta = (int)$item2[1];
      $item3Meta = (int)$item3[1];
      $item4Meta = (int)$item4[1];
      $item5Meta = (int)$item5[1];
      $item6Meta = (int)$item6[1];
      $item7Meta = (int)$item7[1];
      $item8Meta = (int)$item8[1];
      $item9Meta = (int)$item9[1];
      $item10Meta = (int)$item10[1];
      $item11Meta = (int)$item11[1];
      $item12Meta = (int)$item12[1];
      $item13Meta = (int)$item13[1];
      $item14Meta = (int)$item14[1];
      $item15Meta = (int)$item15[1];
      $item16Meta = (int)$item16[1];
      $item17Meta = (int)$item17[1];
      $item18Meta = (int)$item18[1];
      $item1Name = (string)$item1[2];
      $item2Name = (string)$item2[2];
      $item3Name = (string)$item3[2];
      $item4Name = (string)$item4[2];
      $item5Name = (string)$item5[2];
      $item6Name = (string)$item6[2];
      $item7Name = (string)$item7[2];
      $item8Name = (string)$item8[2];
      $item9Name = (string)$item9[2];
      $item10Name = (string)$item10[2];
      $item11Name = (string)$item12[2];
      $item12Name = (string)$item12[2];
      $item13Name = (string)$item13[2];
      $item14Name = (string)$item14[2];
      $item15Name = (string)$item15[2];
      $item16Name = (string)$item16[2];
      $item17Name = (string)$item17[2];
      $item18Name = (string)$item18[2];
      $inv->setItem(0, ItemFactory::getInstance()->get($item1Id, $item1Meta, 1)->setCustomName($item1Name));
      $inv->setItem(1, ItemFactory::getInstance()->get($item2Id, $item2Meta, 1)->setCustomName($item2Name));
      $inv->setItem(2, ItemFactory::getInstance()->get($item3Id, $item3Meta, 1)->setCustomName($item3Name));
      $inv->setItem(3, ItemFactory::getInstance()->get($item4Id, $item4Meta, 1)->setCustomName($item4Name));
      $inv->setItem(4, ItemFactory::getInstance()->get($item5Id, $item5Meta, 1)->setCustomName($item5Name));
      $inv->setItem(5, ItemFactory::getInstance()->get($item6Id, $item6Meta, 1)->setCustomName($item6Name));
      $inv->setItem(6, ItemFactory::getInstance()->get($item7Id, $item7Meta, 1)->setCustomName($item7Name));
      $inv->setItem(7, ItemFactory::getInstance()->get($item8Id, $item8Meta, 1)->setCustomName($item8Name));
      $inv->setItem(8, ItemFactory::getInstance()->get($item9Id, $item9Meta, 1)->setCustomName($item9Name));
      $inv->setItem(9, ItemFactory::getInstance()->get($item10Id, $item10Meta, 1)->setCustomName($item10Name));
      $inv->setItem(10, ItemFactory::getInstance()->get($item11Id, $item11Meta, 1)->setCustomName($item11Name));
      $inv->setItem(11, ItemFactory::getInstance()->get($item12Id, $item12Meta, 1)->setCustomName($item12Name));
      $inv->setItem(12, ItemFactory::getInstance()->get($item13Id, $item13Meta, 1)->setCustomName($item13Name));
      $inv->setItem(13, ItemFactory::getInstance()->get($item14Id, $item14Meta, 1)->setCustomName($item14Name));
      $inv->setItem(14, ItemFactory::getInstance()->get($item15Id, $item15Meta, 1)->setCustomName($item15Name));
      $inv->setItem(15, ItemFactory::getInstance()->get($item16Id, $item16Meta, 1)->setCustomName($item16Name));
      $inv->setItem(16, ItemFactory::getInstance()->get($item17Id, $item17Meta, 1)->setCustomName($item17Name));
      $inv->setItem(17, ItemFactory::getInstance()->get($item18Id, $item18Meta, 1)->setCustomName($item18Name));
    }
    if($this->api->getLevel($player, "Lumberjack") >= 2)
    {
      if($this->Window !== "Single-Chest")
      {
        $menu->send($player);
        $this->Window = "Single-Chest";
      }
    }else{
      $this->MainGUI($player);
      $player->sendMessage("§cPotionBag not unlocked");
    }
  }
  
  public function ShopMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 2 && $itemOutMeta === 0)
        {
          $this->BlockCategoryMenu($player);
        }elseif($itemOutId === 383 && $itemOutMeta === 0)
        {
          $this->WorkerShop($player, 1);
        }elseif($itemOutId === 364 && $itemOutMeta === 0)
        {
          $this->FoodShopMenu($player, 1);
        }elseif($itemOutId === 311 && $itemOutMeta === 0)
        {
          $this->ArmorShopMenu($player, 1);
        }elseif($itemOutId === 279 && $itemOutMeta === 0)
        {
          $this->ToolShopMenu($player, 1);
        }elseif($itemOutId === 265 && $itemOutMeta === 0)
        {
          $this->MineralShopMenu($player);
        }elseif($itemOutId === 355 && $itemOutMeta === 5)
        {
          $this->DecorationShopMenu($player);
        }elseif($itemOutId === 6 && $itemOutMeta === 0)
        {
          $this->NatureShopMenu($player, 1);
        }elseif($itemOutId === 373 && $itemOutMeta === 21)
        {
          $this->PotionCategoryMenu($player);
        }elseif($itemOutId === 403 && $itemOutMeta === 0)
        {
          $this->EnchantShopMenu($player);
        }elseif($itemOutId === 377 && $itemOutMeta === 0)
        {
          $this->UtilShopMenu($player);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(403, 0, 1)->setCustomName("§r §bEnchants §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(355, 5, 1)->setCustomName("§r §eDecorations §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(364, 0, 1)->setCustomName("§r §2Food §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §bArmor §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(6, 0, 1)->setCustomName("§r §aNatural §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(2, 0, 1)->setCustomName("§r §eBlocks §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(265, 0, 1)->setCustomName("§r §9Minerals §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §cTools §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(373, 21, 1)->setCustomName("§r §5Potions §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(377, 0, 1)->setCustomName("§r §8Utils §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(383, 0, 1)->setCustomName("§r §3Workers §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §7cExit §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function BlockCategoryMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 17 && $itemOutMeta === 0)
        {
          $this->WoodShopMenu($player, 1);
        }elseif($itemOutId === 98 && $itemOutMeta === 0)
        {
          $this->StoneShopMenu($player, 1);
        }elseif($itemOutId === 241 && $itemOutMeta === 5)
        {
          $this->RainbowShopMenu($player, 1);
        }elseif($itemOutId === 2 && $itemOutMeta === 0)
        {
          $this->DirtShopMenu($player);
        }elseif($itemOutId === 56 && $itemOutMeta === 0)
        {
          $this->MineralBlockShopMenu($player);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r §aWood §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(98, 0, 1)->setCustomName("§r §7Stone §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(241, 5, 1)->setCustomName("§r §bRainbow §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(2, 0, 1)->setCustomName("§r §6Dirt §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(56, 0, 1)->setCustomName("§r §bMinerals §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  public function PotionCategoryMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 373 && $itemOutMeta === 21)
        {
          $this->PotionShopMenu($player, 1);
        }elseif($itemOutId === 438 && $itemOutMeta === 21)
        {
          $this->SplashPotionShopMenu($player, 1);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(373, 21, 1)->setCustomName("§r §bPotion §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(438, 21, 1)->setCustomName("§r §bSplash Potion §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function WoodShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BlockCategoryMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->WoodShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->WoodShopMenu($player, ($page - 1));
          }
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(17, 0, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(17, 2, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(17, 1, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(17, 3, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(17, 4, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(17, 5, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(5, 0, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(5, 2, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(5, 1, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(5, 3, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(5, 4, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(5, 5, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(158, 0, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(158, 2, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(158, 1, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(158, 3, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(158, 4, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(158, 5, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(53, 0, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(135, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(134, 0, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(136, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(163, 0, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(164, 0, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(85, 0, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(85, 2, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(85, 1, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(85, 3, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(85, 4, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(85, 5, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(107, 0, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(184, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(183, 0, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(185, 0, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(187, 0, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(186, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 3)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(324, 0, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(428, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(427, 0, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(429, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(430, 0, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(431, 0, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(96, 0, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(-146, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(-149, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(-148, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(-145, 0, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(-147, 0, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(323, 0, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(65, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(58, 0, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(-203, 0, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(54, 0, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(-213, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r {$this->api->getRarity($Item)} §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function StoneShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BlockCategoryMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->StoneShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->StoneShopMenu($player, ($page - 1));
          }
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(1, 0, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(1, 5, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(4, 0, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(48, 0, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(98, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(98, 1, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(1, 3, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(-180, 0, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(-171, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(67, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(-179, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(109, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(-175, 0, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(-170, 0, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(-166, 2, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(-162, 3, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(44, 3, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(182, 5, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(44, 5, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(-166, 0, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(-162, 4, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(98, 2, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(87, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(201, 0, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(168, 0, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(121, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(24, 0, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(179, 0, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(98, 3, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(112, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(201, 2, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(168, 2, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(206, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(24, 1, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(179, 1, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(-183, 0, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(215, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(1, 1, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(168, 0, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(45, 0, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(24, 3, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(179, 3, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 3)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(139, 5, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(139, 10, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(139, 4, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(139, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(139, 1, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(139, 9, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(139, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(139, 7, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(139, 8, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(139, 3, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(139, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(139, 12, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(139, 0, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(139, 2, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(139, 11, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 4)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(128, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(180, 0, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(114, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(-4, 0, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(-177, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(-176, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(-184, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(-3, 0, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(-178, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(108, 0, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(-169, 0, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(203, 0, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 5)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(44, 1, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(182, 0, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(44, 7, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(182, 2, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(182, 6, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(-162, 1, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(182, 7, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(182, 3, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(-162, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(44, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(-162, 6, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(-162, 1, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function RainbowShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BlockCategoryMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOut->getName() !== "§r §7 §r" && $itemOut->getName() !== "§r §ePrivious Page §r" && $itemOut->getName() !== "§r §aNext Page §r")
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->RainbowShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->RainbowShopMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(237, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(237, 8, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(237, 14, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(237, 1, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(237, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(237, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(237, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(237, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(237, 9, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(237, 11, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(237, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(237, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(237, 10, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(237, 12, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(237, 15, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(236, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(236, 8, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(236, 14, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(236, 1, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(236, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(236, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(236, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(236, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(236, 9, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(236, 11, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(236, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(236, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(236, 10, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(236, 12, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(236, 15, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 3)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(159, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(159, 8, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(159, 14, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(159, 1, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(159, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(159, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(159, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(159, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(159, 9, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(159, 11, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(159, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(159, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(159, 10, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(159, 12, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(159, 15, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 4)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(220, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(228, 0, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(234, 0, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(221, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(224, 0, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(225, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(233, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(231, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(229, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(223, 0, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(226, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(222, 0, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(219, 0, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(232, 0, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(235, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 5)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(35, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(35, 8, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(35, 14, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(35, 1, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(35, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(35, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(35, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(35, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(35, 9, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(35, 11, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(35, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(35, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(35, 10, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(35, 12, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(35, 15, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 6)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(171, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(171, 8, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(171, 14, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(171, 1, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(171, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(171, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(171, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(171, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(171, 9, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(171, 11, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(171, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(171, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(171, 10, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(171, 12, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(171, 15, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 7)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(241, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(241, 8, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(241, 14, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(241, 1, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(241, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(241, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(241, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(241, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(241, 9, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(241, 11, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(241, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(241, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(241, 10, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(241, 12, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(241, 15, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 8)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(160, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(160, 8, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(160, 14, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(160, 1, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(160, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(160, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(160, 13, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(160, 9, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(160, 11, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(160, 6, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(160, 2, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(160, 10, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(160, 12, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(160, 15, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function DirtShopMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BlockCategoryMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(3, 0, 1));
    $inv->setItem(12, ItemFactory::getInstance()->get(3, 0x1, 1));
    $inv->setItem(13, ItemFactory::getInstance()->get(2, 0, 1));
    $inv->setItem(14, ItemFactory::getInstance()->get(60, 0, 1));
    $inv->setItem(15, ItemFactory::getInstance()->get(198, 0, 1));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(243, 0, 1));
    $inv->setItem(21, ItemFactory::getInstance()->get(12, 0, 1));
    $inv->setItem(22, ItemFactory::getInstance()->get(110, 0, 1));
    $inv->setItem(23, ItemFactory::getInstance()->get(12, 1, 1));
    $inv->setItem(24, ItemFactory::getInstance()->get(88, 0, 1));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function MineralBlockShopMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->BlockCategoryMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(173, 0, 1));
    $inv->setItem(12, ItemFactory::getInstance()->get(-139, 0, 1));
    $inv->setItem(13, ItemFactory::getInstance()->get(129, 0, 1));
    $inv->setItem(14, ItemFactory::getInstance()->get(41, 0, 1));
    $inv->setItem(15, ItemFactory::getInstance()->get(42, 0, 1));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(16, 0, 1));
    $inv->setItem(21, ItemFactory::getInstance()->get(15, 0, 1));
    $inv->setItem(22, ItemFactory::getInstance()->get(56, 0, 1));
    $inv->setItem(23, ItemFactory::getInstance()->get(73, 0, 1));
    $inv->setItem(24, ItemFactory::getInstance()->get(14, 0, 1));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(22, 0, 1));
    $inv->setItem(30, ItemFactory::getInstance()->get(133, 0, 1));
    $inv->setItem(31, ItemFactory::getInstance()->get(21, 0, 1));
    $inv->setItem(32, ItemFactory::getInstance()->get(57, 0, 1));
    $inv->setItem(33, ItemFactory::getInstance()->get(152, 0, 1));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function FoodShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOut->getName() !== "§r §7 §r" && $itemOut->getName() !== "§r §ePrivious Page §r" && $itemOut->getName() !== "§r §aNext Page §r")
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->FoodShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->FoodShopMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(297, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(391, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(396, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(260, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(322, 0, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(320, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(364, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(424, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(366, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(412, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ArmorShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOut->getName() !== "§r §7 §r" && $itemOut->getName() !== "§r §ePrivious Page §r" && $itemOut->getName() !== "§r §aNext Page §r")
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->ArmorShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->ArmorShopMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(298, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(299, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(300, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(301, 0, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(314, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(315, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(316, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(317, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 3)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(306, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(307, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(308, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(309, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 4)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(310, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(311, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(312, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(313, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function NatureShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOut->getName() !== "§r §7 §r" && $itemOut->getName() !== "§r §ePrivious Page §r" && $itemOut->getName() !== "§r §aNext Page §r")
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->NatureShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->NatureShopMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(10, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(6, 0, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(6, 1, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(6, 2, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(6, 3, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(6, 4, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(6, 5, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(392, 0, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(295, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(361, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(362, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(458, 0, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(391, 0, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(37, 0, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(38, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(38, 1, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(38, 3, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(38, 7, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(175, 0, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(10, ItemFactory::getInstance()->get(79, 0, 1));
      $inv->setItem(11, ItemFactory::getInstance()->get(174, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(-11, 0, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(80, 0, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(351, 15, 1));
      $inv->setItem(16, ItemFactory::getInstance()->get(353, 0, 1));
      $inv->setItem(19, ItemFactory::getInstance()->get(86, 0, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(103, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(81, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(106, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(338, 0, 1));
      $inv->setItem(25, ItemFactory::getInstance()->get(-163, 0, 1));
      $inv->setItem(28, ItemFactory::getInstance()->get(32, 0, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(127, 0, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(39, 0, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(375, 0, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(367, 0, 1));
      $inv->setItem(34, ItemFactory::getInstance()->get(32, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function DecorationShopMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(-203, 0, 1));
    $inv->setItem(11, ItemFactory::getInstance()->get(54, 0, 1));
    $inv->setItem(12, ItemFactory::getInstance()->get(25, 0, 1));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(84, 0, 1));
    $inv->setItem(15, ItemFactory::getInstance()->get(47, 0, 1));
    $inv->setItem(16, ItemFactory::getInstance()->get(-194, 0, 1));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(116, 0, 1));
    $inv->setItem(20, ItemFactory::getInstance()->get(355, 14, 1));
    $inv->setItem(21, ItemFactory::getInstance()->get(397, 5, 1));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(425, 0, 1));
    $inv->setItem(24, ItemFactory::getInstance()->get(355, 11, 1));
    $inv->setItem(25, ItemFactory::getInstance()->get(145, 0, 1));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(397, 0, 1));
    $inv->setItem(29, ItemFactory::getInstance()->get(218, 14, 1));
    $inv->setItem(30, ItemFactory::getInstance()->get(169, 0, 1));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(89, 0, 1));
    $inv->setItem(33, ItemFactory::getInstance()->get(218, 11, 1));
    $inv->setItem(34, ItemFactory::getInstance()->get(397, 2, 1));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function MineralShopMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(264, 0, 1));
    $inv->setItem(13, ItemFactory::getInstance()->get(351, 4, 1));
    $inv->setItem(14, ItemFactory::getInstance()->get(388, 0, 1));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(331, 0, 1));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(266, 0, 1));
    $inv->setItem(30, ItemFactory::getInstance()->get(335, 0, 1));
    $inv->setItem(31, ItemFactory::getInstance()->get(263, 0, 1));
    $inv->setItem(32, ItemFactory::getInstance()->get(263, 1, 1));
    $inv->setItem(33, ItemFactory::getInstance()->get(265, 0, 1));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          if($Item->getId() !== 264 && $Item->getId() !== 388)
          {
            $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          }else{
            $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lUcommon §r\n§r §7Price: §r$Price §r");
          }
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ManageMembersMenu(Player $player)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bMembers §3List");
    $menu->setListener(
      function (InvMenuTransaction $transaction): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->SettingsMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 397 && $itemOutMeta === 3)
        {
          $member = str_replace(["§r §e", " §r"], ["", ""], $itemOutName);
          $this->ManageMemberMenu($player, $member);
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $i = 1;
    $island = $this->api->getSource()->getPlayerFile($player)->get("Island");
    $members = array();
    foreach(scandir($this->api->getSource()->getDataFolder() . "players") as $key => $file)
    {
      if(is_file($this->api->getSource()->getDataFolder() . "players/$file"))
      {
        $playerFile = new Config($this->api->getSource()->getDataFolder() . "players/$file", Config::YAML, [
          ]);
        if($playerFile->get("Island") === $island && ($playerFile->getNested("Co-Op.Role") === "Owner" || $playerFile->getNested("Co-Op.Role") === "Co-Owner"))
        {
          $members = $playerFile->getNested("Co-Op.Members");
        }
      }
    }
    foreach($members as $member)
    {
      $slot = $i + 19;
      $inv->setItem($slot, ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("§r §e$member §r"));
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
    
  public function ManageMemberMenu(Player $player, string $member)
  {
    $menu = $this->SingleChest;
    $menu->setName("§Manage §3Member");
    $menu->setListener(
      function(InvMenuTransaction $transaction) use($member): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 35)
        {
          if($player->getName() !== $member)
          {
            if($itemOutMeta === 5)
            {
              if($this->api->CoOpPromote($member))
              {
                $role = $this->api->getCoOpRole($member);
                $player->sendMessage("§apromoted §e$member §ato §e$role");
                $player->removeCurrentWindow();
              }
            }elseif($itemOutMeta == 14)
            {
              if($this->api->CoOpDemote($member))
              {
                $role = $this->api->getCoOpRole($member);
                $player->sendMessage("§ademoted §e$member §ato §e$role");
                $player->removeCurrentWindow();
              }
            }
          }
        }elseif($itemOutId === 152)
        {
          if($player->getName() !== $member)
          {
            if($this->api->removeCoOp($member))
            {
              $player->sendMessage("§aremoved §e$member from Co-Op");
            }else{
              $player->sendMessage("§ccan't remove the player from Co-Op");
            }
            $player->removeCurrentWindow();
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $demotedRole = array(
      "Builder" => "-",
      "Member" => "Builder",
      "Senior-Member" => "Member",
      "Co-Owner" => "Senior-Member",
      "Owner" => "-"
      );
    $demoted = $demotedRole[$this->api->getCoOpRole($member)];
    $inv->setItem(11, ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§r §cDemote §r\n§r §7 §r\n§r §7Demoted Role: §e$demoted §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(152, 0, 1)->setCustomName("§r §cRemove §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $promotedRole = array(
      "Builder" => "Member",
      "Member" => "Senior-Member",
      "Senior-Member" => "Co-Owner",
      "Co-Owner" => "-",
      "Owner" => "-"
      );
    $promoted = $promotedRole[$this->api->getCoOpRole($member)];
    $inv->setItem(15, ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§r §aPromote §r\n§r §7 §r\n§r §7Prmoted Role: §e$promoted §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Single-Chest")
    {
      $menu->send($player);
      $this->Window = "Single-Chest";
    }
  }
  
  public function PotionShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->PotionCategoryMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOut->getName() !== "§r §7 §r" && $itemOut->getName() !== "§r §ePrivious Page §r" && $itemOut->getName() !== "§r §aNext Page §r")
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->PotionShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->PotionShopMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(373, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(373, 1, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(373, 2, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(373, 3, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(373, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(373, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(373, 6, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(373, 7, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(373, 8, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(373, 9, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(373, 10, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(373, 11, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(373, 12, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(373, 13, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(373, 14, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(373, 15, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(373, 16, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(373, 17, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(373, 18, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(373, 19, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(373, 20, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(373, 21, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(373, 22, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(373, 23, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(373, 24, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(373, 25, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(373, 26, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(373, 27, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(373, 28, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(373, 29, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 3)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(373, 30, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(373, 31, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(373, 32, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(373, 33, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(373, 34, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(373, 35, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(373, 36, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(373, 37, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(373, 38, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(373, 39, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(373, 40, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(373, 41, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(373, 42, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(373, 43, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(373, 42, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
    
  public function SplashPotionShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->PotionCategoryMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOut->getName() !== "§r §7 §r" && $itemOut->getName() !== "§r §ePrivious Page §r" && $itemOut->getName() !== "§r §aNext Page §r")
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->SplashPotionShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->SplashPotionShopMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(438, 0, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(438, 1, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(438, 2, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(438, 3, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(438, 4, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(438, 5, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(438, 6, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(438, 7, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(438, 8, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(438, 9, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(438, 10, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(438, 11, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(438, 12, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(438, 13, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(438, 14, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(438, 15, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(438, 16, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(438, 17, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(438, 18, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(438, 19, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(438, 20, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(438, 21, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(438, 22, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(438, 23, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(438, 24, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(438, 25, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(438, 26, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(438, 27, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(438, 28, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(438, 29, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 3)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(438, 30, 1));
      $inv->setItem(12, ItemFactory::getInstance()->get(438, 31, 1));
      $inv->setItem(13, ItemFactory::getInstance()->get(438, 32, 1));
      $inv->setItem(14, ItemFactory::getInstance()->get(438, 33, 1));
      $inv->setItem(15, ItemFactory::getInstance()->get(438, 34, 1));
      $inv->setItem(20, ItemFactory::getInstance()->get(438, 35, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(438, 36, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(438, 37, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(438, 38, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(438, 39, 1));
      $inv->setItem(29, ItemFactory::getInstance()->get(438, 40, 1));
      $inv->setItem(30, ItemFactory::getInstance()->get(438, 41, 1));
      $inv->setItem(31, ItemFactory::getInstance()->get(438, 42, 1));
      $inv->setItem(32, ItemFactory::getInstance()->get(438, 43, 1));
      $inv->setItem(33, ItemFactory::getInstance()->get(438, 42, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function EnchantShopMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut);
          if(!is_null($Price))
          {
            $itemOut_nbt = $itemOut->getNamedTag();
            $customName = $itemOut_nbt->getString("EnchantId");
            $this->BuyMenu($player, $itemOut, $Price, $customName);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(340, 1, 1)->setCustomName("§bRespiration"));
    $inv->setItem(11, ItemFactory::getInstance()->get(340, 2, 1)->setCustomName("§bBlast Protection"));
    $inv->setItem(12, ItemFactory::getInstance()->get(340, 3, 1)->setCustomName("§bFeather Falling"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(340, 4, 1)->setCustomName("§bThorns"));
    $inv->setItem(15, ItemFactory::getInstance()->get(340, 5, 1)->setCustomName("§bFire Protection"));
    $inv->setItem(16, ItemFactory::getInstance()->get(340, 6, 1)->setCustomName("§bProtection"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(340, 7, 1)->setCustomName("§bPower"));
    $inv->setItem(19, ItemFactory::getInstance()->get(340, 8, 1)->setCustomName("§bPunch"));
    $inv->setItem(21, ItemFactory::getInstance()->get(340, 9, 1)->setCustomName(" §bFlame"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(340, 10, 1)->setCustomName("§bInfinity"));
    $inv->setItem(24, ItemFactory::getInstance()->get(340, 11, 1)->setCustomName("§bKnockBack"));
    $inv->setItem(25, ItemFactory::getInstance()->get(340, 12, 1)->setCustomName("§bFire Aspect"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(340, 13, 1)->setCustomName("§bSharpness"));
    $inv->setItem(29, ItemFactory::getInstance()->get(340, 14, 1)->setCustomName("§bProjectile Protection"));
    $inv->setItem(30, ItemFactory::getInstance()->get(340, 15, 1)->setCustomName("§bUnbreaking"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(340, 16, 1)->setCustomName("§bSkill Touch"));
    $inv->setItem(33, ItemFactory::getInstance()->get(340, 17, 1)->setCustomName("§bEfficiency"));
    $inv->setItem(34, ItemFactory::getInstance()->get(340, 18, 1)->setCustomName("§bMending"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $nbt = clone $Item->getNamedTag();
          $nbt->setString("EnchantId", "{$Item->getCustomName()}");
          $Item->setNamedTag($nbt);
          $Item->setCustomName("§r {$Item->getCustomName()} §r\n§r §lCommon §r\n§r §7Price: §r".$Price * $Item->getMeta()." §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function UtilShopMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(345, 0, 1));
    $inv->setItem(12, ItemFactory::getInstance()->get(325, 8, 1));
    $inv->setItem(13, ItemFactory::getInstance()->get(325, 0, 1));
    $inv->setItem(14, ItemFactory::getInstance()->get(325, 10, 1));
    $inv->setItem(15, ItemFactory::getInstance()->get(347, 0, 1));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(288, 0, 1));
    $inv->setItem(21, ItemFactory::getInstance()->get(-206, 0, 1));
    $inv->setItem(22, ItemFactory::getInstance()->get(368, 0, 1));
    $inv->setItem(23, ItemFactory::getInstance()->get(450, 0, 1));
    $inv->setItem(24, ItemFactory::getInstance()->get(30, 0, 1));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(321, 0, 1));
    $inv->setItem(30, ItemFactory::getInstance()->get(352, 0, 1));
    $inv->setItem(31, ItemFactory::getInstance()->get(372, 0, 1));
    $inv->setItem(32, ItemFactory::getInstance()->get(369, 0, 1));
    $inv->setItem(33, ItemFactory::getInstance()->get(389, 0, 1));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ToolShopMenu(Player $player, int $page)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOut->getName() !== "§r §7 §r" && $itemOut->getName() !== "§r §ePrivious Page §r" && $itemOut->getName() !== "§r §aNext Page §r")
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $this->BuyMenu($player, $itemOut, $Price);
          }
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->ToolShopMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->ToolShopMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(268, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(270, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(271, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(269, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(290, 0, 1));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(272, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(274, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(275, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(273, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(291, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 3)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(283, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(285, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(286, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(284, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(294, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 4)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(267, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(257, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(258, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(256, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(292, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 5)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(276, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(278, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(279, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(277, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(293, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 6)
    {
      $inv->setItem(20, ItemFactory::getInstance()->get(261, 0, 1));
      $inv->setItem(21, ItemFactory::getInstance()->get(259, 0, 1));
      $inv->setItem(22, ItemFactory::getInstance()->get(262, 0, 1));
      $inv->setItem(23, ItemFactory::getInstance()->get(359, 0, 1));
      $inv->setItem(24, ItemFactory::getInstance()->get(346, 0, 1));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $Item->setCustomName("§r {$Item->getVanillaName()} §r\n§r §lCommon §r\n§r §7Price: §r$Price §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function WorkerShop(Player $player, int $page): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use($page): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->ShopMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->WorkerShop($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->WorkerShop($player, ($page - 1));
          }
        }elseif($itemOutId !== 160)
        {
          $Shop = $this->api->getShopAPI();
          $Price = $Shop->getPrice($itemOut, false);
          if(!is_null($Price))
          {
            $itemOut_nbt = $itemOut->getNamedTag();
            $customName = $itemOut_nbt->getString("WorkerId");
            $this->buyMenu($player, $itemOut, $Price, $customName);
          }
        }
          
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(122, 1, 1)->setCustomName("§eStone Worker"));
      $inv->setItem(12, ItemFactory::getInstance()->get(122, 2, 1)->setCustomName("§rCobbleStone Worker"));
      $inv->setItem(13, ItemFactory::getInstance()->get(122, 3, 1)->setCustomName("§eCoal Worker"));
      $inv->setItem(14, ItemFactory::getInstance()->get(122, 4, 1)->setCustomName("§eGold Worker"));
      $inv->setItem(15, ItemFactory::getInstance()->get(122, 5, 1)->setCustomName("§e§eIron Worker"));
      $inv->setItem(20, ItemFactory::getInstance()->get(122, 6, 1)->setCustomName("§eLapis Worker"));
      $inv->setItem(21, ItemFactory::getInstance()->get(122, 7, 1)->setCustomName("§eDiamond Worker"));
      $inv->setItem(22, ItemFactory::getInstance()->get(122, 8, 1)->setCustomName("§eEmerald Worker"));
      $inv->setItem(23, ItemFactory::getInstance()->get(122, 9, 1)->setCustomName("§eGranite Worker"));
      $inv->setItem(24, ItemFactory::getInstance()->get(122, 10, 1)->setCustomName("§eDiorite Worker"));
      $inv->setItem(29, ItemFactory::getInstance()->get(122, 11, 1)->setCustomName("§eAndesite Worker"));
      $inv->setItem(30, ItemFactory::getInstance()->get(122, 12, 1)->setCustomName("§eBeetroot Worker"));
      $inv->setItem(31, ItemFactory::getInstance()->get(122, 13, 1)->setCustomName("§eWheat Worker"));
      $inv->setItem(32, ItemFactory::getInstance()->get(122, 14, 1)->setCustomName("§eCarrot Worker"));
      $inv->setItem(33, ItemFactory::getInstance()->get(122, 15, 1)->setCustomName("§ePotato Worker"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(122, 16, 1)->setCustomName("§eOak Lumberjack"));
      $inv->setItem(12, ItemFactory::getInstance()->get(122, 17, 1)->setCustomName("§eSpruce Lumberjack"));
      $inv->setItem(13, ItemFactory::getInstance()->get(122, 18, 1)->setCustomName("§eBirch Lumberjack"));
      $inv->setItem(14, ItemFactory::getInstance()->get(122, 19, 1)->setCustomName("§eJungle Lumberjack"));
      $inv->setItem(15, ItemFactory::getInstance()->get(122, 20, 1)->setCustomName("§eAcacia Lumberjack"));
      $inv->setItem(20, ItemFactory::getInstance()->get(122, 21, 1)->setCustomName("§eDark Oak Worker"));
      $inv->setItem(21, ItemFactory::getInstance()->get(122, 22, 1)->setCustomName("§eZombie Slayer"));
      $inv->setItem(22, ItemFactory::getInstance()->get(122, 23, 1)->setCustomName("§eSkeleton Slayer"));
      $inv->setItem(23, ItemFactory::getInstance()->get(122, 24, 1)->setCustomName("§eSpider Slayer"));
      $inv->setItem(24, ItemFactory::getInstance()->get(122, 25, 1)->setCustomName("§eEnderman Slayer"));
      $inv->setItem(29, ItemFactory::getInstance()->get(122, 26, 1)->setCustomName("§eBlaze Slayer"));
      $inv->setItem(30, ItemFactory::getInstance()->get(122, 27, 1)->setCustomName("§eWitherSkeleton Slayer"));
      $inv->setItem(31, ItemFactory::getInstance()->get(122, 28, 1)->setCustomName("§eCow Slayer"));
      $inv->setItem(32, ItemFactory::getInstance()->get(122, 29, 1)->setCustomName("§ePig Slayer"));
      $inv->setItem(33, ItemFactory::getInstance()->get(122, 30, 1)->setCustomName("§eSheep Slayer"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    for($i = 0; $i <= 53; $i++)
    {
      $Item = $inv->getItem($i);
      $Name = $Item->getName();
      if($Name !== "§r §7 §r" && $Name !== "§r §ePrivious Page §r" && $Name !== "§r §aNext Page §r" && $Name !== "§r §cBack §r\n§r §7click to go back to the privious menu §r" && $Name !== "§r §cExit §r\n§r §7click to exit the menu §r")
      {
        $Shop = $this->api->getShopAPI();
        $Price = $Shop->getPrice($Item, false);
        if(!is_null($Price))
        {
          $nbt = clone $Item->getNamedTag();
          $nbt->setString("WorkerId", "{$Item->getCustomName()}");
          $Item->setNamedTag($nbt);
          $Item->setCustomName("§r {$Item->getCustomName()} §r\n§r §lCommon §r\n§r §7Price: §r".$Price." §r");
          $inv->setItem($i, $Item);
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function BuyMenu(Player $player, Item $item, int|float $Price, string $customName = "")
  {
    $menu = $this->SingleChest;
    $menu->setName("§3Shop");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($item, $Price, $customName) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $itemInName = $transaction->getIn()->getName();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemOutName = $transaction->getOut()->getName();
        if($customName === "")
        {
          $customName = $item->getVanillaName();
        }
        if($transaction->getAction()->getSlot() === 13)
        {
          $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
          $economy->getPlayerBalance($player->getName(),
            ClosureContext::create(
              function (?int $balance) use($player, $item, $inv, $Price, $economy, $customName): void
              {
                if(!is_null($balance))
                {
                  $totalPrice = $inv->getItem(13)->getCount() * $Price;
                  if($balance >= $totalPrice)
                  {
                    if($item->getId() !== 264 && $item->getId() !== 388)
                    {
                      $item->setCustomName("§r {$customName} §r\n§r §lCommon §r");
                    }else{
                      $item->setCustomName("§r {$customName} §r\n§r §lUncommon §r");
                    }
                    $economy->subtractFromPlayerBalance($player->getName(), $totalPrice);
                    $player->sendMessage("§aSuccessfully Bought §e".$inv->getItem(13)->getCount()."x $customName §aFor §e$totalPrice");
                    if($player->getInventory()->canAddItem($item->setCount($inv->getItem(13)->getCount())))
                    {
                      $player->getInventory()->addItem($item->setCount($inv->getItem(13)->getCount()));
                    }else{
                      $world = $player->getWorld();
                      $position = $player->getPosition();
                      $x = $position->getX();
                      $y = $position->getY();
                      $z = $position->getZ();
                      $world->dropItem(new Vector3($x, $y, $z), $item->setCount($inv->getItem(13)->getCount()));
                    }
                  }else{
                    $player->sendMessage("§cError You Don't Have Enough Money");
                  }
                }else{
                  $player->sendMessage("§cError You Don't Have Enough Money");
                }
              },
            ));
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            if($item->getMaxStackSize() > $inv->getItem(13)->getCount())
            {
              $i = $inv->getItem(13)->setCount($inv->getItem(13)->getCount() + 1);
              $total = $Price * $i->getCount();
              if($i->getId() !== 264 && $i->getId() !== 388)
              {
                $i->setCustomName("§r {$customName} §r\n§r §lCommon §r\n§r §7Price: §r$total §r");
              }else{
                $i->setCustomName("§r {$customName} §r\n§r §lUncommon §r\n§r §7Price: §r$total §r");
              }
              $inv->setItem(13, $i);
            }
          }elseif($itemOutMeta === 14)
          {
            if($inv->getItem(13)->getCount() > 1)
            {
              $i = $inv->getItem(13)->setCount($inv->getItem(13)->getCount() - 1);
              $total = $Price * $i->getCount();
              if($i->getId() !== 264 && $i->getId() !== 388)
              {
                $i->setCustomName("§r {$customName} §r\n§r §lCommon §r\n§r §7Price: §r$total §r");
              }else{
                $i->setCustomName("§r {$customName} §r\n§r §lUncommon §r\n§r §7Price: §r$total §r");
              }
              $inv->setItem(13, $i);
            }
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(4, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(5, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $item_a = $item;
    $inv->setItem(13, $item_a);
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §l§c- §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §l§a+ §r"));
    if($this->Window !== "Single-Chest")
    {
      $menu->send($player);
      $this->Window = "Single-Chest";
    }
  }
  
  public function RecipeMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 311 && $itemOutMeta === 0)
        {
          $this->ArmorRecipesMenu($player);
        }elseif($itemOutId === 383 && $itemOutMeta === 0)
        {
          $this->WorkerRecipesMenu($player, 1);
        }elseif($itemOutId === 264 && $itemOutMeta === 0)
        {
          $this->EnchantedRecipesMenu($player);
        }elseif($itemOutId === 278 && $itemOutMeta === 0)
        {
          $this->ToolRecipesMenu($player);
        }elseif($itemOutId === 403 && $itemOutMeta === 0)
        {
          $this->CustomRecipesMenu($player);
        }elseif($itemOutId === 325 && $itemOutMeta === 10)
        {
          $this->UpgradeRecipesMenu($player);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(403, 0, 1)->setCustomName("§r §eCustom Item Recipes §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(325, 10, 1)->setCustomName("§r §eWorker Upgrade Recipes §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §eArmor Recipes §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eTool Recipes §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(383, 0, 1)->setCustomName("§r §eWorker Recipes §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(264, 0, 1)->setCustomName("§r §eEnchanted Recipes §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function WorkerRecipesMenu(Player $player, $page): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bWorker §3Recipes");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu, $page) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 122)
        {
          $this->Recipe($player, $itemOutName, "Worker-$page");
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->RecipeMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId === 160)
        {
          if($itemOutMeta === 5)
          {
            $this->WorkerRecipesMenu($player, ($page + 1));
          }elseif($itemOutMeta === 4)
          {
            $this->WorkerRecipesMenu($player, ($page - 1));
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($page === 1)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(122, 1, 1)->setCustomName("§r §eStone Worker §r"));
      $inv->setItem(12, ItemFactory::getInstance()->get(122, 2, 1)->setCustomName("§r §eStone Worker §r"));
      $inv->setItem(13, ItemFactory::getInstance()->get(122, 3, 1)->setCustomName("§r §eStone Worker §r"));
      $inv->setItem(14, ItemFactory::getInstance()->get(122, 4, 1)->setCustomName("§r §eStone Worker §r"));
      $inv->setItem(15, ItemFactory::getInstance()->get(122, 5, 1)->setCustomName("§r §eCobbleStone Worker §r"));
      $inv->setItem(20, ItemFactory::getInstance()->get(122, 6, 1)->setCustomName("§r §eCoal Worker §r"));
      $inv->setItem(21, ItemFactory::getInstance()->get(122, 7, 1)->setCustomName("§r §eGold Worker §r"));
      $inv->setItem(22, ItemFactory::getInstance()->get(122, 8, 1)->setCustomName("§r §eIron Worker §r"));
      $inv->setItem(23, ItemFactory::getInstance()->get(122, 9, 1)->setCustomName("§r §eLapis Worker §r"));
      $inv->setItem(24, ItemFactory::getInstance()->get(122, 10, 1)->setCustomName("§r §eEmerald Worker §r"));
      $inv->setItem(29, ItemFactory::getInstance()->get(122, 11, 1)->setCustomName("§r §eWheat Worker §r"));
      $inv->setItem(30, ItemFactory::getInstance()->get(122, 12, 1)->setCustomName("§r §eBeetroot Worker §r"));
      $inv->setItem(31, ItemFactory::getInstance()->get(122, 13, 1)->setCustomName("§r §eDiamond Worker §r"));
      $inv->setItem(32, ItemFactory::getInstance()->get(122, 14, 1)->setCustomName("§r §ePotato Worker §r"));
      $inv->setItem(33, ItemFactory::getInstance()->get(122, 15, 1)->setCustomName("§r §eCarrot Worker §r"));
      $inv->setItem(53, ItemFactory::getInstance()->get(160, 5, 1)->setCustomName("§r §aNext Page §r"));
    }elseif($page === 2)
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(122, 1, 1)->setCustomName("§r §eOak Lumberjack §r"));
      $inv->setItem(12, ItemFactory::getInstance()->get(122, 2, 1)->setCustomName("§r §eSpruce Lumberjack §r"));
      $inv->setItem(13, ItemFactory::getInstance()->get(122, 3, 1)->setCustomName("§r §eBirch Lumberjack §r"));
      $inv->setItem(14, ItemFactory::getInstance()->get(122, 4, 1)->setCustomName("§r §eJungle Lumberjack §r"));
      $inv->setItem(15, ItemFactory::getInstance()->get(122, 5, 1)->setCustomName("§r §eAcacia Lumberjack §r"));
      $inv->setItem(20, ItemFactory::getInstance()->get(122, 6, 1)->setCustomName("§r §eDark Oak Lumberjack §r"));
      $inv->setItem(21, ItemFactory::getInstance()->get(122, 7, 1)->setCustomName("§r §eZombie Slayer §r"));
      $inv->setItem(22, ItemFactory::getInstance()->get(122, 8, 1)->setCustomName("§r §eSkeleton Slayer §r"));
      $inv->setItem(23, ItemFactory::getInstance()->get(122, 9, 1)->setCustomName("§r §eWitherSkeleton Slayer §r"));
      $inv->setItem(24, ItemFactory::getInstance()->get(122, 10, 1)->setCustomName("§r §eBlaze Slayer §r"));
      $inv->setItem(29, ItemFactory::getInstance()->get(122, 11, 1)->setCustomName("§r §eEnderman Slayer §r"));
      $inv->setItem(30, ItemFactory::getInstance()->get(122, 12, 1)->setCustomName("§r §eSpider Slayer §r"));
      $inv->setItem(31, ItemFactory::getInstance()->get(122, 13, 1)->setCustomName("§r §eCow Slayer §r"));
      $inv->setItem(32, ItemFactory::getInstance()->get(122, 14, 1)->setCustomName("§r §ePig Slayer §r"));
      $inv->setItem(33, ItemFactory::getInstance()->get(122, 15, 1)->setCustomName("§r §eSheep Slayer §r"));
      $inv->setItem(45, ItemFactory::getInstance()->get(160, 4, 1)->setCustomName("§r §ePrivious Page §r"));
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function EnchantedRecipesMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bEnchanted §3Recipes");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->RecipeMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $this->Recipe($player, $itemOutName, "Enchanted");
        }
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(263, 0, 1)->setCustomName("§r §0Enchanted Coal §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r §eEnchanted Gold §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(265, 0, 1)->setCustomName("§r Enchanted Iron §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(351, 4, 1)->setCustomName("§r §1Enchanted Lapis §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(388, 0, 1)->setCustomName("§r §aEnchanted Emerald §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(264, 0, 1)->setCustomName("§r §bEnchanted Diamond §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(296, 0, 1)->setCustomName("§r §eEnchanted Wheat §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(392, 0, 1)->setCustomName("§r §eEnchanted Potato §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(391, 0, 1)->setCustomName("§r §6Enchanted Carrot §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r §6Enchanted Oak Log §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ArmorRecipesMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bArmor §3Recipes");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->RecipeMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $this->Recipe($player, $itemOutName, "Armor");
        }
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(298, 0, 1)->setCustomName("§r §bMiner Helmet §r\n§r §7 §r\n§r §eFull Set Bonus §r\n§r §7- Speed Effect §r\n§r §7- Night Vision Effect §r\n§r §7- Haste Effect §r\n§r §7 §r\n§r §l§cRare §r")->setCustomColor(new Color(128, 128, 128)));
    $inv->setItem(12, ItemFactory::getInstance()->get(299, 0, 1)->setCustomName("§r §bMiner Chestplate §r\n§r §7 §r\n§r §eFull Set Bonus §r\n§r §7- Speed Effect §r\n§r §7- Night Vision Effect §r\n§r §7- Haste Effect §r\n§r §7 §r\n§r §l§cRare §r")->setCustomColor(new Color(128, 128, 128)));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(300, 0, 1)->setCustomName("§r §bMiner Leggings §r\n§r §7 §r\n§r §eFull Set Bonus §r\n§r §7- Speed Effect §r\n§r §7- Night Vision Effect §r\n§r §7- Haste Effect §r\n§r §7 §r\n§r §l§cRare §r")->setCustomColor(new Color(128, 128, 128)));
    $inv->setItem(15, ItemFactory::getInstance()->get(301, 0, 1)->setCustomName("§r §bMiner Boots §r\n§r §7 §r\n§r §eFull Set Bonus §r\n§r §7- Speed Effect §r\n§r §7- Night Vision Effect §r\n§r §7- Haste Effect §r\n§r §7 §r\n§r §l§cRare §r")->setCustomColor(new Color(128, 128, 128)));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(298, 0, 1)->setCustomName("§r §eFarmer Helmet §r")->setCustomColor(new Color(255, 255, 0)));
    $inv->setItem(21, ItemFactory::getInstance()->get(299, 0, 1)->setCustomName("§r §eFarmer Chestplate §r")->setCustomColor(new Color(255, 255, 0)));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(300, 0, 1)->setCustomName("§r §eFarmer Leggings §r")->setCustomColor(new Color(255, 255, 0)));
    $inv->setItem(24, ItemFactory::getInstance()->get(301, 0, 1)->setCustomName("§r §eFarmer Boots §r")->setCustomColor(new Color(255, 255, 0)));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(298, 0, 1)->setCustomName("§r §6Lumberjack Helmet §r")->setCustomColor(new Color(150, 75, 0)));
    $inv->setItem(30, ItemFactory::getInstance()->get(299, 0, 1)->setCustomName("§r §6Lumberjack Chestplate §r")->setCustomColor(new Color(150, 75, 0)));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(300, 0, 1)->setCustomName("§r §6Lumberjack Leggings §r")->setCustomColor(new Color(150, 75, 0)));
    $inv->setItem(33, ItemFactory::getInstance()->get(301, 0, 1)->setCustomName("§r §6Lumberjack Boots §r")->setCustomColor(new Color(150, 75, 0)));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ToolRecipesMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bTool §3Recipes");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->RecipeMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $this->Recipe($player, $itemOutName, "Tool");
        }
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(276, 0, 1)->setCustomName("§r §cSlayer Sword §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §bMiner Pickaxe §r\n§r §7 §r\n§r - §e25% Chance Of Double Drops §r\n§r - §eAuto Smelter §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §6Lumberjack Axe §r\n§r §7 §r\n§r - §e36% Chance Of Double Drops §r\n§r - §e20% Chance Of Double Chopping §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §eFarmer Hoe §r\n§r §7 §r\n§r - §e40% Chance Of Double Drops §r\n§r - §eHoes 3x3 Area §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function UpgradeRecipesMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bUpgrade §3Recipes");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->RecipeMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $this->Recipe($player, $itemOutName, "Upgrade");
        }
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(23, 0, 1)->setCustomName("§r §cExpander §r\n§r §7Worker Upgrade §r\n§r §7Changes Worker Area To 7x7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(264, 0, 1)->setCustomName("§r §bSpreading §r\n§r §7Worker Upgrade §r\n§r §7Spreads Diamond In Worker Inv §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(175, 0, 1)->setCustomName("§r §eFlowEngine §r\n§r §7Worker Upgrade §r\n§r §7Generates Enchanted Item In Worker Inventory §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(325, 10, 1)->setCustomName("§r §eLavaster §r\n§r §7Worker Upgrade §r\n§r §7Increases Worker Working Speed §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(125, 0, 1)->setCustomName("§r §6Compactor §r\n§r §7Worker Upgrade §r\n§r §7Compacts Items To Their Block Form §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function CustomRecipesMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bCustom §3Recipes");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->RecipeMenu($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutId !== 160)
        {
          $this->Recipe($player, $itemOutName, "Custom");
        }
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(3, 0, 1)->setCustomName("§r §eBuilder's Essence §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(369, 0, 1)->setCustomName("§r §eWand §r\n§r §7 §r\n§r §7Used To Craft Wands §r\n§r §7 §r\n§r §lUncommon §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(369, 0, 1)->setCustomName("§r §eBuilder Wand §r\n§r §7 §r\n§r §7- Left-Click To Open GUI §r\n§r §7- Right-Click To Use §r\n§r §7 §r\n§r §l§cRare §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(388, 0, 1)->setCustomName("§r §aEmerald Blade §r\n§r §7 §r\n§r §7Emerald Blade Gets Stronger As More Money §r\n§r §7You Carry In Your Purse §r\n§r §7 §r\n§r §l§6Legendary §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eWorker-Chest §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function Recipe(Player $player, string $recipeName, string $priviousMenu, ?Item $item = null)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§eRecipe");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu, $priviousMenu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 131 && $itemOutMeta === 0)
        {
          $this->Recipe($player, $inv->getItem(24)->getName(), $priviousMenu, $inv->getItem(24));
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          if($priviousMenu === "Worker-1")
          {
            $this->WorkerRecipesMenu($player, 1);
          }elseif($priviousMenu === "Worker-2")
          {
            $this->WorkerRecipesMenu($player, 2);
          }elseif($priviousMenu === "Enchanted")
          {
            $this->EnchantedRecipesMenu($player);
          }elseif($priviousMenu === "Armor")
          {
            $this->ArmorRecipesMenu($player);
          }elseif($priviousMenu === "Tool")
          {
            $this->ToolRecipesMenu($player);
          }elseif($priviousMenu === "Upgrade")
          {
            $this->UpgradeRecipesMenu($player);
          }elseif($priviousMenu === "Custom")
          {
            $this->CustomRecipesMenu($player);
          }elseif($priviousMenu === "null")
          {
            $player->removeCurrentWindow();
          }
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }elseif($itemOutName !== "§r §7 §r")
        {
          $customRecipe = null;
          $recipe = null;
          $recipe = $this->api->matchRecipeByOutput($itemOut);
          $recipeName = $itemOutName;
          foreach($this->api->getAllRecipes() as $recipeFile)
          {
            $file = $this->source->getInstance()->getRecipeFile(str_replace(".yml", "", $recipeFile));
            $key = $file->get("Recipe");
            if(((string) $key[9][3]) === ((string) $recipeName))
            {
              $customRecipe = $key;
            }
          }
          if(!is_null($recipe) || !is_null($customRecipe))
          {
            $this->Recipe($player, $itemOutName, $priviousMenu, $itemOut);
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(131, 0, 1)->setCustomName("§r §eDifferent Recipe §r\n§r §7 §r\n§r §7View A Different Recipe With The Same Output §r"));
    $recipe = null;
    foreach($this->api->getAllRecipes() as $recipeFile)
    {
      $file = $this->source->getInstance()->getRecipeFile(str_replace(".yml", "", $recipeFile));
      $key = $file->get("Recipe");
      if(((string) $key[9][3]) === ((string) $recipeName))
      {
        $recipe = $key;
      }
    }
    if(!is_null($recipe))
    {
      $A = $recipe[0];
      $B = $recipe[1];
      $C = $recipe[2];
      $D = $recipe[3];
      $E = $recipe[4];
      $F = $recipe[5];
      $G = $recipe[6];
      $H = $recipe[7];
      $I = $recipe[8];
      $R = $recipe[9];
      $AItem = ItemFactory::getInstance()->get($A[0], $A[1], $A[2])->setCustomName("{$A[3]}");
      $BItem = ItemFactory::getInstance()->get($B[0], $B[1], $B[2])->setCustomName("{$B[3]}");
      $CItem = ItemFactory::getInstance()->get($C[0], $C[1], $C[2])->setCustomName("{$C[3]}");
      $DItem = ItemFactory::getInstance()->get($D[0], $D[1], $D[2])->setCustomName("{$D[3]}");
      $EItem = ItemFactory::getInstance()->get($E[0], $E[1], $E[2])->setCustomName("{$E[3]}");
      $FItem = ItemFactory::getInstance()->get($F[0], $F[1], $F[2])->setCustomName("{$F[3]}");
      $GItem = ItemFactory::getInstance()->get($G[0], $G[1], $G[2])->setCustomName("{$G[3]}");
      $HItem = ItemFactory::getInstance()->get($H[0], $H[1], $H[2])->setCustomName("{$H[3]}");
      $IItem = ItemFactory::getInstance()->get($I[0], $I[1], $I[2])->setCustomName("{$I[3]}");
      if(array_key_exists(4, $R))
      {
        $RItem = ItemFactory::getInstance()->get($R[0], $R[1], $R[2])->setCustomName("{$R[3]}");
      }else{
        $RItem = ItemFactory::getInstance()->get($R[0], $R[1], $R[2])->setCustomName("{$R[3]}");
      }
      $inv->setItem(11, $AItem);
      $inv->setItem(12, $BItem);
      $inv->setItem(13, $CItem);
      $inv->setItem(20, $DItem);
      $inv->setItem(21, $EItem);
      $inv->setItem(22, $FItem);
      $inv->setItem(29, $GItem);
      $inv->setItem(30, $HItem);
      $inv->setItem(31, $IItem);
      $inv->setItem(24, $RItem);
    }elseif(!is_null($item))
    {
      $recipe = $this->api->matchRecipeByOutput($item);
      if(!is_null($recipe))
      {
        if($recipe instanceof ShapedRecipe)
        {
          for($y = 0; $y < $recipe->getHeight(); ++$y)
          {
            for($x = 0; $x < $recipe->getWidth(); ++$x)
            {
              $i = $recipe->getIngredient($x, $y);
              if($i->getMeta() === -1)
              {
                $meta = 0;
              }else{
                $meta = $i->getMeta();
              }
              $ingredient = ItemFactory::getInstance()->get($i->getId(), $meta, $i->getCount());
              $ingredient->setCustomName("§r {$ingredient->getVanillaName()} §r\n§r §lCommon §r");
              if($y === 0)
              {
                $slot = $x;
                if($inv->getItem($slot + 11)->getName() !== "§r §7 §r")
                {
                  $inv->setItem($slot + 11, $ingredient);
                }
              }elseif($y === 1)
              {
                $slot = ($x * $y) + 3;
                if($inv->getItem($slot + 17)->getName() !== "§r §7 §r")
                {
                  $inv->setItem($slot + 17, $ingredient);
                }
              }elseif($y === 2)
              {
                if($x === 0)
                {
                  $slot = 6;
                }elseif($x === 1)
                {
                  $slot = 7;
                }elseif($x === 2)
                {
                  $slot = 8;
                }
                if($inv->getItem($slot + 23)->getName() !== "§r §7 §r")
                {
                  $inv->setItem($slot + 23, $ingredient);
                }
              }
            }
          }
        }elseif($recipe instanceof ShapelessRecipe)
        {
          $i = 0;
          foreach($recipe->getIngredients() as $i)
          {
            if($i->getMeta() === -1)
            {
              $meta = 0;
            }else{
              $meta = $i->getMeta();
            }
            $ingredient = ItemFactory::getInstance()->get($i->getId(), $meta, $i->getCount());
            $ingredient->setCustomName("§r {$ingredient->getVanillaName()} §r\n§r §lCommon §r");
            if($i < 3)
            {
              $slot = 11;
              $inv->setItem($slot, $ingredient);
            }elseif($i < 6)
            {
              $slot = 17;
              $inv->setItem($slot, $ingredient);
            }elseif($i < 9)
            {
              $slot = 23;
              $inv->setItem($slot, $ingredient);
            }
            $i++;
          }
        }
        $inv->setItem(24, $recipe->getResults()[0]);
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function PetsMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3Island");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 397 && $itemOutMeta === 0)
        {
          $pet = str_replace(["§r §e", " §r"], ["", ""], $itemOutName);
          new PetManageMenu($player, $pet);
        }elseif($itemOutId === 262 && $itemOutMeta === 0)
        {
          $this->MainGUI($player);
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(1, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(2, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(3, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(4, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(5, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(6, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(7, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(8, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(9, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(10, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(13, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(16, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(17, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(18, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(22, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(25, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(26, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(27, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(28, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(31, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(34, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(35, ItemFactory::getInstance()->get(0, 0, 0));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(262, 0, 1)->setCustomName("§r §cBack §r\n§r §7click to go back to the privious menu §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->api->getPlayerPets($player) !== [])
    {
      $i = 0;
      foreach($this->api->getPlayerPets($player) as $pet)
      {
        $inv->setItem($i, ItemFactory::getInstance()->get(397, 0, 1)->setCustomName("§r §e$pet §r"));
        $i++;
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function EnderChestMenu(Player $player): void
  {
    $menu = $this->SingleChest;
    $menu->setName("§9Ender Chest");
    $menu->setListener(
      function (InvMenuTransaction $transaction) : InvMenuTransactionResult
      {
        return $transaction->continue();
      }
    );
    $menu->setInventoryCloseListener(
      function (Player $player, $inv): void
      {
        $contents = $inv->getContents();
        $player->getEnderInventory()->setContents($contents);
      }
    );
    $contents = $player->getEnderInventory()->getContents();
    $menu->getInventory()->setContents($contents);
    if($this->Window !== "Single-Chest")
    {
      $menu->send($player);
      $this->Window = "Single-Chest";
    }
  }
  
  public function KitMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§3Kits");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($itemOutId === 54 && $itemOutMeta === 0)
        {
          if($itemOutName === "§r §eDaily Kit §r\n§r §cUnClaimed §r")
          {
            $player->removeCurrentWindow();
            $playerFile = $this->source->getInstance()->getPlayerFile($player);
            $playerFile->setNested("Kits.Daily.Claimed", true);
            $playerFile->save();
            $playerInventory = $player->getInventory();
            $playerInventory->addItem(ItemFactory::getInstance()->get(272, 0, 1)->setCustomName("§r §eDaily Sword §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(274, 0, 1)->setCustomName("§r §eDaily Pickaxe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(275, 0, 1)->setCustomName("§r §eDaily Axe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(273, 0, 1)->setCustomName("§r §eDaily Shovel §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(291, 0, 1)->setCustomName("§r §eDaily Hoe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(424, 0, 8)->setCustomName("§r §eDaily Mutton §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 16));
            $playerInventory->addItem(ItemFactory::getInstance()->get(298, 0, 1)->setCustomName("§r §eDaily Helmet §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(299, 0, 1)->setCustomName("§r §eDaily Chestplate §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(300, 0, 1)->setCustomName("§r §eDaily Leggings §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(301, 0, 1)->setCustomName("§r §eDaily Boots §r"));
            $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "Daily"), 20);
          }elseif($itemOutName === "§r §eWeekly Kit §r\n§r §cUnClaimed §r")
          {
            $player->removeCurrentWindow();
            $playerFile = $this->source->getInstance()->getPlayerFile($player);
            $playerFile->setNested("Kits.Weekly.Claimed", true);
            $playerFile->save();
            $playerInventory = $player->getInventory();
            $playerInventory->addItem(ItemFactory::getInstance()->get(267, 0, 1)->setCustomName("§r §eWeekly Sword §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(257, 0, 1)->setCustomName("§r §eWeekly Pickaxe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(258, 0, 1)->setCustomName("§r §eWeekly Axe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(256, 0, 1)->setCustomName("§r §eWeekly Shovel §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(292, 0, 1)->setCustomName("§r §eWeekly Hoe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(364, 0, 32)->setCustomName("§r §eWeekly Steak §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 32));
            $playerInventory->addItem(ItemFactory::getInstance()->get(306, 0, 1)->setCustomName("§r §eWeekly Helmet §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(307, 0, 1)->setCustomName("§r §eWeekly Chestplate §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(308, 0, 1)->setCustomName("§r §eWeekly Leggings §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(309, 0, 1)->setCustomName("§r §eWeekly Boots §r"));
            $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "Weekly"), 20);
          }elseif($itemOutName === "§r §eMonthly Kit §r\n§r §cUnClaimed §r")
          {
            $player->removeCurrentWindow();
            $playerFile = $this->source->getInstance()->getPlayerFile($player);
            $playerFile->setNested("Kits.Monthly.Claimed", true);
            $playerFile->save();
            $playerInventory = $player->getInventory();
            $playerInventory->addItem(ItemFactory::getInstance()->get(276, 0, 1)->setCustomName("§r §eMonthly Sword §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eMonthly Pickaxe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §eMonthly Axe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(277, 0, 1)->setCustomName("§r §eMonthly Shovel §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §eMonthly Hoe §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(320, 0, 64)->setCustomName("§r §eMonthly Porkchop §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 64));
            $playerInventory->addItem(ItemFactory::getInstance()->get(310, 0, 1)->setCustomName("§r §eMonthly Helmet §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §eMonthly Chestplate §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(312, 0, 1)->setCustomName("§r §eMonthly Leggings §r"));
            $playerInventory->addItem(ItemFactory::getInstance()->get(313, 0, 1)->setCustomName("§r §eMonthly Boots §r"));
            $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "Monthly"), 20);
          }elseif($itemOutName === "§r §eVip Kit §r\n§r §cUnClaimed §r")
          {
            $rank = Ranks::getInstance()->getRankOfPlayer($player->getName());
            if($rank === "Vip" || $rank === "YouTube" || $rank === "YT" || $rank === "Mvp" || $rank === "Sroudy" || $rank === "Donator")
            {
              $player->removeCurrentWindow();
              $playerFile = $this->source->getInstance()->getPlayerFile($player);
              $playerFile->setNested("Kits.Vip.Claimed", true);
              $playerFile->save();
              $playerInventory = $player->getInventory();
              $prot_id = StringToEnchantmentParser::getInstance()->parse("protection");
              $prot = new EnchantmentInstance($prot_id, 1);
              $sharp_id = StringToEnchantmentParser::getInstance()->parse("sharpness");
              $sharp = new EnchantmentInstance($sharp_id, 1);
              $playerInventory->addItem(ItemFactory::getInstance()->get(276, 0, 1)->setCustomName("§r §eVip Sword §r")->addEnchantment($sharp));
              $playerInventory->addItem(ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eVip Pickaxe §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §eVip Axe §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(277, 0, 1)->setCustomName("§r §eVip Shovel §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §eVip Hoe §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(320, 0, 64)->setCustomName("§r §eVip Porkchop §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 64));
              $playerInventory->addItem(ItemFactory::getInstance()->get(310, 0, 1)->setCustomName("§r §eVip Helmet §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §eVip Chestplate §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(312, 0, 1)->setCustomName("§r §eVip Leggings §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(313, 0, 1)->setCustomName("§r §eVip Boots §r")->addEnchantment($prot));
              $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "Vip"), 20);
            }
          }elseif($itemOutName === "§r §eYouTuber Kit §r\n§r §cUnClaimed §r")
          {
            $rank = Ranks::getInstance()->getRankOfPlayer($player->getName());
            if($rank === "YouTube" || $rank === "YT" || $rank === "Mvp" || $rank === "Sroudy" || $rank === "Donator")
            {
              $player->removeCurrentWindow();
              $playerFile = $this->source->getInstance()->getPlayerFile($player);
              $playerFile->setNested("Kits.YouTuber.Claimed", true);
              $playerFile->save();
              $playerInventory = $player->getInventory();
              $prot_id = StringToEnchantmentParser::getInstance()->parse("protection");
              $prot = new EnchantmentInstance($prot_id, 2);
              $sharp_id = StringToEnchantmentParser::getInstance()->parse("sharpness");
              $sharp = new EnchantmentInstance($sharp_id, 1);
              $eff_id = StringToEnchantmentParser::getInstance()->parse("efficiency");
              $eff = new EnchantmentInstance($eff_id, 1);
              $playerInventory->addItem(ItemFactory::getInstance()->get(276, 0, 1)->setCustomName("§r §eYouTuber Sword §r")->addEnchantment($sharp));
              $playerInventory->addItem(ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eYouTuber Pickaxe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §eYouTuber Axe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(277, 0, 1)->setCustomName("§r §eYouTuber Shovel §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §eYouTuber Hoe §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(320, 0, 64)->setCustomName("§r §eYouTuber Porkchop §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 64));
              $playerInventory->addItem(ItemFactory::getInstance()->get(310, 0, 1)->setCustomName("§r §eYouTuber Helmet §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §eYouTuber Chestplate §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(312, 0, 1)->setCustomName("§r §eYouTuber Leggings §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(313, 0, 1)->setCustomName("§r §eYouTuber Boots §r")->addEnchantment($prot));
              $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "YouTuber"), 20);
            }
          }elseif($itemOutName === "§r §eMvp Kit §r\n§r §cUnClaimed §r")
          {
            $rank = Ranks::getInstance()->getRankOfPlayer($player->getName());
            if($rank === "Mvp" || $rank === "Sroudy" || $rank === "Donator")
            {
              $player->removeCurrentWindow();
              $playerFile = $this->source->getInstance()->getPlayerFile($player);
              $playerFile->setNested("Kits.Mvp.Claimed", true);
              $playerFile->save();
              $playerInventory = $player->getInventory();
              $prot_id = StringToEnchantmentParser::getInstance()->parse("protection");
              $prot = new EnchantmentInstance($prot_id, 3);
              $sharp_id = StringToEnchantmentParser::getInstance()->parse("sharpness");
              $sharp = new EnchantmentInstance($sharp_id, 2);
              $eff_id = StringToEnchantmentParser::getInstance()->parse("efficiency");
              $eff = new EnchantmentInstance($eff_id, 2);
              $playerInventory->addItem(ItemFactory::getInstance()->get(276, 0, 1)->setCustomName("§r §eMvp Sword §r")->addEnchantment($sharp));
              $playerInventory->addItem(ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eMvp Pickaxe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §eMvp Axe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(277, 0, 1)->setCustomName("§r §eMvp Shovel §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §eMvp Hoe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(320, 0, 64)->setCustomName("§r §eMvp Porkchop §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 64));
              $playerInventory->addItem(ItemFactory::getInstance()->get(310, 0, 1)->setCustomName("§r §eMvp Helmet §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §eMvp Chestplate §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(312, 0, 1)->setCustomName("§r §eMvp Leggings §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(313, 0, 1)->setCustomName("§r §eMvp Boots §r")->addEnchantment($prot));
              $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "Mvp"), 20);
            }
          }elseif($itemOutName === "§r §eSroudy Kit §r\n§r §cUnClaimed §r")
          {
            $rank = Ranks::getInstance()->getRankOfPlayer($player->getName());
            if($rank === "Sroudy" || $rank === "Donator")
            {
              $player->removeCurrentWindow();
              $playerFile = $this->source->getInstance()->getPlayerFile($player);
              $playerFile->setNested("Kits.Sroudy.Claimed", true);
              $playerFile->save();
              $playerInventory = $player->getInventory();
              $prot_id = StringToEnchantmentParser::getInstance()->parse("protection");
              $prot = new EnchantmentInstance($prot_id, 4);
              $sharp_id = StringToEnchantmentParser::getInstance()->parse("sharpness");
              $sharp = new EnchantmentInstance($sharp_id, 3);
              $knock_id = StringToEnchantmentParser::getInstance()->parse("knockback");
              $knock = new EnchantmentInstance($knock_id, 1);
              $eff_id = StringToEnchantmentParser::getInstance()->parse("efficiency");
              $eff = new EnchantmentInstance($eff_id, 3);
              $playerInventory->addItem(ItemFactory::getInstance()->get(276, 0, 1)->setCustomName("§r §eSroudy Sword §r")->addEnchantment($sharp)->addEnchantment($knock));
              $playerInventory->addItem(ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eSroudy Pickaxe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §eSroudy Axe §r")->addEnchantment($eff)->addEnchantment($sharp));
              $playerInventory->addItem(ItemFactory::getInstance()->get(277, 0, 1)->setCustomName("§r §eSroudy Shovel §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §eSroudy Hoe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(320, 0, 64)->setCustomName("§r §eSroudy Porkchop §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 64));
              $playerInventory->addItem(ItemFactory::getInstance()->get(310, 0, 1)->setCustomName("§r §eSroudy Helmet §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §eSroudy Chestplate §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(312, 0, 1)->setCustomName("§r §eSroudy Leggings §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(313, 0, 1)->setCustomName("§r §eSroudy Boots §r")->addEnchantment($prot));
              $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "Sroudy"), 20);
            }
          }elseif($itemOutName === "§r §eDonator Kit §r\n§r §cUnClaimed §r")
          {
            $rank = Ranks::getInstance()->getRankOfPlayer($player->getName());
            if($rank === "Donator")
            {
              $player->removeCurrentWindow();
              $playerFile = $this->source->getInstance()->getPlayerFile($player);
              $playerFile->setNested("Kits.Donator.Claimed", true);
              $playerFile->save();
              $playerInventory = $player->getInventory();
              $prot_id = StringToEnchantmentParser::getInstance()->parse("protection");
              $prot = new EnchantmentInstance($prot_id, 4);
              $sharp_id = StringToEnchantmentParser::getInstance()->parse("sharpness");
              $sharp = new EnchantmentInstance($sharp_id, 3);
              $knock_id = StringToEnchantmentParser::getInstance()->parse("knockback");
              $knock = new EnchantmentInstance($knock_id, 2);
              $fire_id = StringToEnchantmentParser::getInstance()->parse("fire_aspect");
              $fire = new EnchantmentInstance($fire_id, 2);
              $eff_id = StringToEnchantmentParser::getInstance()->parse("efficiency");
              $eff = new EnchantmentInstance($eff_id, 3);
              $playerInventory->addItem(ItemFactory::getInstance()->get(276, 0, 1)->setCustomName("§r §eDonator Sword §r")->addEnchantment($sharp)->addEnchantment($knock)->addEnchantment($fire));
              $playerInventory->addItem(ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r §eDonator Pickaxe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r §eDonator Axe §r")->addEnchantment($eff)->addEnchantment($sharp)->addEnchantment($knock));
              $playerInventory->addItem(ItemFactory::getInstance()->get(277, 0, 1)->setCustomName("§r §eDonator Shovel §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r §eDonator Hoe §r")->addEnchantment($eff));
              $playerInventory->addItem(ItemFactory::getInstance()->get(320, 0, 64)->setCustomName("§r §eDonator Porkchop §r"));
              $playerInventory->addItem(ItemFactory::getInstance()->get(17, 0, 64));
              $playerInventory->addItem(ItemFactory::getInstance()->get(310, 0, 1)->setCustomName("§r §eDonator Helmet §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(311, 0, 1)->setCustomName("§r §eDonator Chestplate §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(312, 0, 1)->setCustomName("§r §eDonator Leggings §r")->addEnchantment($prot));
              $playerInventory->addItem(ItemFactory::getInstance()->get(313, 0, 1)->setCustomName("§r §eDonator Boots §r")->addEnchantment($prot));
              $this->source->getInstance()->getScheduler()->scheduleRepeatingTask(new KitTask($this->source, $player->getName(), "Donator"), 20);
            }
          }
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Daily.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Daily.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Daily.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $DailyClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $DailyClaimed = "§cUnClaimed";
    }
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Weekly.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Weekly.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Weekly.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $WeeklyClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $WeeklyClaimed = "§cUnClaimed";
    }
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Monthly.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Monthly.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Monthly.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $MonthlyClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $MonthlyClaimed = "§cUnClaimed";
    }
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Vip.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Vip.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Vip.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $VipClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $VipClaimed = "§cUnClaimed";
    }
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.YouTuber.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.YouTuber.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.YouTuber.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $YouTuberClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $YouTuberClaimed = "§cUnClaimed";
    }
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Mvp.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Mvp.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Mvp.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $MvpClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $MvpClaimed = "§cUnClaimed";
    }
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Sroudy.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Sroudy.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Sroudy.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $SroudyClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $SroudyClaimed = "§cUnClaimed";
    }
    if($this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Donator.Claimed"))
    {
      $time = $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Donator.MaxTime") - $this->source->getInstance()->getPlayerFile($player)->getNested("Kits.Donator.Time");
      $format = $this->api->changeNumericFormat($time, "time");
      $DonatorClaimed = "§aClaimed\n§8Time Left: §7$format";
    }else{
      $DonatorClaimed = "§cUnClaimed";
    }
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(1, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(2, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(3, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(6, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(7, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(8, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(9, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(10, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eDaily Kit §r\n§r $DailyClaimed §r"));
    $inv->setItem(11, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(12, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eWeekly Kit §r\n§r $WeeklyClaimed §r"));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eMonthly Kit §r\n§r $MonthlyClaimed §r"));
    $inv->setItem(15, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(16, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eVip Kit §r\n§r $VipClaimed §r"));
    $inv->setItem(17, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(18, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(19, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(20, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(21, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(24, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(25, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(26, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(27, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(28, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eYouTuber Kit §r\n§r $YouTuberClaimed §r"));
    $inv->setItem(29, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(30, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eMvp Kit §r\n§r $MvpClaimed §r"));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eSroudy Kit §r\n§r $SroudyClaimed §r"));
    $inv->setItem(33, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(34, ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("§r §eDonator Kit §r\n§r $DonatorClaimed §r"));
    $inv->setItem(35, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(36, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(37, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(38, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(39, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(42, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(43, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(44, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function TradeMenu(Player $player_1, Player $player_2)
  {
    $this->ItemsReturned = false;
    $this->TradeAccepted = false;
    $menu = $this->DoubleChest;
    $menu->setName("§3Trading");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($player_1, $player_2) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $slot = $transaction->getAction()->getSlot();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        $array_1 = array(0, 1, 2, 3, 9, 10, 11, 12, 18, 19, 20, 21, 27, 28, 29, 30, 36, 37, 38, 39);
        $array_2 = array(5, 6, 7, 8, 14, 15, 16, 17, 23, 24, 25, 26, 32, 33, 34, 35, 41, 42, 43, 44);
        $array_3 = array(46, 47, 48, 49, 50, 51, 52);
        if($slot === 45)
        {
          if($player->getName() === $player_1->getName())
          {
            if($itemOutMeta === 5)
            {
              $inv->setItem(45, ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§caccept"));
            }elseif($itemOutMeta === 14)
            {
              $inv->setItem(45, ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§aaccepted"));
            }
          }
        }elseif($slot === 53)
        {
          if($player->getName() === $player_2->getName())
          {
            if($itemOutMeta === 5)
            {
              $inv->setItem(53, ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§caccept"));
            }elseif($itemOutMeta === 14)
            {
              $inv->setItem(53, ItemFactory::getInstance()->get(35, 5, 1)->setCustomName("§aaccepted"));
            }
          }
        }elseif(in_array($slot, $array_1))
        {
          if($player->getName() === $player_1->getName())
          {
            if($inv->getItem(45)->getMeta() === 14)
            {
              if(!$this->TradeAccepted)
              {
                return $transaction->continue();
              }
            }
          }
        }elseif(in_array($slot, $array_2))
        {
          if($player->getName() === $player_2->getName())
          {
            if($inv->getItem(53)->getMeta() === 14)
            {
              if(!$this->TradeAccepted)
              {
                return $transaction->continue();
              }
            }
          }
        }elseif(in_array($slot, $array_3))
        {
          $player->removeCurrentWindow();
        }
        
        if($inv->getItem(45)->getMeta() === 5 && $inv->getItem(53)->getMeta() === 5)
        {
          $this->TradeAccepted = true;
          $this->source->getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function() use($inv, $array_1, $array_2, $player, $player_1, $player_2): void
            {
              if(!$this->ItemsReturned)
              {
                foreach($array_1 as $slot)
                {
                  $item = $inv->getItem($slot);
                  if($item->getId() !== 0)
                  {
                    if($player_2->getInventory()->canAddItem($item))
                    {
                      $player_2->getInventory()->addItem($item);
                    }else{
                      $world = $player_2->getWorld();
                      $world->dropItem($player_2->getPosition(), $item);
                    }
                  }
                }
                foreach($array_2 as $slot)
                {
                  $item = $inv->getItem($slot);
                  if($item->getId() !== 0)
                  {
                    if($player_1->getInventory()->canAddItem($item))
                    {
                      $player_1->getInventory()->addItem($item);
                    }else{
                      $world = $player_1->getWorld();
                      $world->dropItem($player_1->getPosition(), $item);
                    }
                  }
                }
                $this->ItemsReturned = true;
                $player->removeCurrentWindow();
                $player_1->sendMessage("§atrade successful");
                $player_2->sendMessage("§atrade successful");
              }
            }
          ), 60);
        }
        
        return $transaction->discard();
      }
    );
    $menu->setInventoryCloseListener(
      function(Player $player, $inv) use($player_1, $player_2): void
      {
        if(!$this->ItemsReturned)
        {
          $array_1 = array(0, 1, 2, 3, 9, 10, 11, 12, 18, 19, 20, 21, 27, 28, 29, 30, 36, 37, 38, 39);
          $array_2 = array(5, 6, 7, 8, 14, 15, 16, 17, 23, 24, 25, 26, 32, 33, 34, 35, 41, 42, 43, 44);
          foreach($array_1 as $slot)
          {
            $item = $inv->getItem($slot);
            if($item->getId() !== 0)
            {
              if($player_1->getInventory()->canAddItem($item))
              {
                $player_1->getInventory()->addItem($item);
              }else{
                $world = $player_1->getWorld();
                $world->dropItem($player_1->getPosition(), $item);
              }
            }
          }
          foreach($array_2 as $slot)
          {
            $item = $inv->getItem($slot);
            if($item->getId() !== 0)
            {
              if($player_2->getInventory()->canAddItem($item))
              {
                $player_2->getInventory()->addItem($item);
              }else{
                $world = $player_2->getWorld();
                $world->dropItem($player_2->getPosition(), $item);
              }
            }
          }
          $this->ItemsReturned = true;
          $player_1->sendMessage("§ctrade cancelled");
          $player_2->sendMessage("§ctrade cancelled");
        }
        $this->source->getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
          function() use($inv): void
          {
            foreach($inv->getViewers() as $hash => $viewer)
            {
              if($viewer->getCurrentWindow() instanceof $inv)
              {
                $viewer->removeCurrentWindow();
              }
            }
          }
        ), 5);
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(1, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(2, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(3, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(4, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(5, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(6, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(7, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(8, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(9, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(10, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(11, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(12, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(13, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(14, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(15, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(16, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(17, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(18, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(19, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(20, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(21, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(22, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(23, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(24, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(25, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(26, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(27, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(28, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(29, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(30, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(31, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(32, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(33, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(34, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(35, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(36, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(37, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(38, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(39, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(40, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(41, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(42, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(43, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(44, ItemFactory::getInstance()->get(0, 0, 0)->setCustomName(""));
    $inv->setItem(45, ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§aaccept"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(35, 14, 1)->setCustomName("§aaccept"));
    $menu->send($player_1);
    $menu->send($player_2);
    if(count($inv->getViewers()) < 2)
    {
      $this->source->getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
        function() use($inv, $player_1, $player_2): void
        {
          foreach($inv->getViewers() as $hash => $viewer)
          {
            if($viewer->getCurrentWindow() instanceof $inv)
            {
              $viewer->removeCurrentWindow();
              $player_1->sendMessage("§ctrade cancelled");
              $player_2->sendMessage("§ctrade cancelled");
            }
          }
        }
      ), 5);
    }
  }
  
  public function BuilderWandMenu(Player $player, Item $wand)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§6Builder §eWand");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu, $wand) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getId();
        $itemOutId = $transaction->getOut()->getId();
        $itemInMeta = $transaction->getIn()->getMeta();
        $inv = $transaction->getAction()->getInventory();
        $itemOutMeta = $transaction->getOut()->getMeta();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        $wandItem = $this->api->matchItem($wand, $player->getInventory());
        
        if($itemIn instanceof ItemBlock)
        {
          if($itemInId !== 0)
          {
            if(!is_null($wandItem))
            {
              if(!is_null($wandItem[1]->getNamedTag()->getTag("WandId")))
              {
                if(!array_key_exists($wandItem[1]->getNamedTag()->getString("WandId"), EventHandler::getInstance()->builderWandInv))
                {
                  EventHandler::getInstance()->builderWandInv[$wandItem[1]->getNamedTag()->getString("WandId")] = new SimpleInventory(45);
                  EventHandler::getInstance()->builderWandInv[$wandItem[1]->getNamedTag()->getString("WandId")]->addItem($itemIn);
                  $nbt = $wandItem[1]->getNamedTag();
                  $nbt->setString("WandInv", serialize(EventHandler::getInstance()->builderWandInv[$wand->getNamedTag()->getString("WandId")]->getContents()));
                  $player->getInventory()->setItem($wandItem[0], $wandItem[1]->setNamedTag($nbt));
                }else{
                  EventHandler::getInstance()->builderWandInv[$wandItem[1]->getNamedTag()->getString("WandId")]->addItem($itemIn);
                  $nbt = $wandItem[1]->getNamedTag();
                  $nbt->setString("WandInv", serialize(EventHandler::getInstance()->builderWandInv[$wand->getNamedTag()->getString("WandId")]->getContents()));
                  $player->getInventory()->setItem($wandItem[0], $wandItem[1]->setNamedTag($nbt));
                }
              }else{
                $this->source->getInstance()->saveResource("builderwand.yml");
                $builderwandFile = new Config($this->source->getInstance()->getDataFolder() . "builderwand.yml", Config::YAML, [
                  ]);
                $count = $builderwandFile->get("Count", 0) + 1;
                $builderwandFile->setNested("Count", $count);
                $builderwandFile->save();
                $nbt = clone $wandItem[1]->getNamedTag();
                $nbt->setString("WandId", "$count");
                EventHandler::getInstance()->builderWandInv["$count"] = new SimpleInventory(45);
                EventHandler::getInstance()->builderWandInv["$count"]->addItem($itemIn);
                $nbt->setString("WandInv", serialize(EventHandler::getInstance()->builderWandInv["$count"]->getContents()));
                if($wandItem[1]->getCount() === 1)
                {
                  $player->getInventory()->setItem($wandItem[0], $wandItem[1]->setNamedTag($nbt));
                }elseif($wandItem[1]->getCount() > 1)
                {
                  $player->getInventory()->removeItem($wandItem[1]->setCount(1));
                  if($player->getInventory()->canAddItem($wandItem[1]->setCount(1)->setNamedTag($nbt)))
                  {
                    $player->getInventory()->addItem($wandItem[1]->setCount(1)->setNamedTag($nbt));
                  }else{
                    $player->getWorld()->dropItem($player->getPosition(), $wandItem[1]->setCount(1)->setNamedTag($nbt));
                  }
                }
              }
            }
          }
        }else{
          return $transaction->discard();
        }
        if($itemOutName !== "§r §7 §r" && $itemOutName !== "§r §cExit §r\n§r §7click to exit the menu §r")
        {
          if($itemOutId !== 0)
          {
            if(!is_null($wandItem))
            {
              if(!is_null($wandItem[1]->getNamedTag()->getTag("WandId")))
              {
                if(array_key_exists($wandItem[1]->getNamedTag()->getString("WandId"), EventHandler::getInstance()->builderWandInv))
                {
                  EventHandler::getInstance()->builderWandInv[$wandItem[1]->getNamedTag()->getString("WandId")]->removeItem($itemOut);
                  $nbt = $wandItem[1]->getNamedTag();
                  $nbt->setString("WandInv", serialize(EventHandler::getInstance()->builderWandInv[$wandItem[1]->getNamedTag()->getString("WandId")]->getContents()));
                  $player->getInventory()->setItem($wandItem[0], $wandItem[1]->setNamedTag($nbt));
                }
              }
            }
          }
        }elseif($itemOutId === 331 && $itemOutMeta === 0)
        {
          $player->removeCurrentWindow();
          return $transaction->discard();
        }else{
          return $transaction->discard();
        }
        
        return $transaction->continue();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(45, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(46, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(47, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(48, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(49, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(52, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(53, ItemFactory::getInstance()->get(160, 14, 1)->setCustomName("§r §7 §r"));
    $i = 0;
    if(!is_null($wand->getNamedTag()->getTag("WandId")))
    {
      if(array_key_exists($wand->getNamedTag()->getString("WandId"), EventHandler::getInstance()->builderWandInv))
      {
        if(!is_null($wand->getNamedTag()->getTag("WandInv")))
        {
          $contents = unserialize($wand->getNamedTag()->getString("WandInv"));
          foreach(array_reverse($contents, true) as $slot => $item)
          {
            $slot = $i;
            if($slot <= 44)
            {
              $inv->setItem($slot, $item);
              $i++;
            }
          }
        }
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
}