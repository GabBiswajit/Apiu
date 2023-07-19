<?php

/**
*  █████╗ ██████╗ ██╗
* ██╔══██╗██╔══██╗██║
* ███████║██████╔╝██║
* ██╔══██║██╔═══╝ ██║
* ██║  ██║██║     ██║
* ╚═╝  ╚═╝╚═╝     ╚═╝
                   
*/

namespace skyisland;

use pocketmine\Server;
use pocketmine\player\Player;

use skyisland\SkyIsland;
use skyisland\EventHandler;

use Ramsey\Uuid\Uuid;
use pocketmine\item\Item;
use skyisland\entity\Pet;
use skyisland\utils\Grid;
use poketmine\world\World;
use pocketmine\entity\Skin;
use muqsit\invmenu\InvMenu;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use skyisland\entity\Worker;
use skyisland\api\BalanceAPI;
use pocketmine\entity\Entity;
use pocketmine\world\Position;
use skyisland\entity\Assistant;
use pocketmine\entity\Location;
use skyisland\api\PetSystemAPI;
use skyisland\api\VariablesAPI;
use skyisland\task\InterestTask;
use pocketmine\item\ItemFactory;
use skyisland\api\PlayerInfoAPI;
use skyisland\api\ShopSystemAPI;
use pocketmine\block\BlockFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\BlockLegacyIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

class API
{
  
  /** @var API */
  private static $instance;
  
  /** @var SkyIsland */
  private $source;
  
  /** @var Config */
  public $players;
  
  /** @var Config */
  public $config;
  
  /** @var Config */
  public $potionbag;
  
  public function __construct(SkyIsland $source)
  {
    self::$instance = $this;
    $this->source = $source;
    $this->config = $this->getSource()->getConfigFile();
  }
  
  /**
   * @return API
   */
  public static function getInstance(): API
  {
    return self::$instance;
  }
  
  /**
   * @return ShopSystemAPI
   */
  public static function getShopAPI(): ShopSystemAPI
  {
    $Shop = new ShopSystemAPI();
    return $Shop;
  }
  
  /**
   * @return VariablesAPI
   */
  public static function getVariables(): VariablesAPI
  {
    $Variables = VariablesAPI::getInstance();
    return $Variables;
  }
  
  /**
   * @return PlayerInfoAPI
   */
  public static function getPlayerInfo($player): PlayerInfoAPI
  {
    $Info = new PlayerInfoAPI($player);
    return $Info;
  }
  
  /**
   * @return PetSystemAPI
   */
  public static function getPetSystem($player): PetSystemAPI
  {
    $Pet = new PetSystemAPI($player);
    return $Pet;
  }
  
  /**
   * @return BalanceAPI
   */
  public static function getBalanceAPI($player): BalanceAPI
  {
    $Balance = new BalanceAPI($player);
    return $Balance;
  }
  
  public function registerPlayer($player): void
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(!$this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("NameTag", $playerName);
      $playerFile->setNested("Island", $playerName);
      $playerFile->setNested("Friends", []);
      $playerFile->setNested("Info.Speed", 0);
      $playerFile->setNested("Info.Damage", 0);
      $playerFile->setNested("Info.Strength", 0);
      $playerFile->setNested("Info.MaxHealth", 0);
      $playerFile->setNested("Info.Fortune.Miner", 0);
      $playerFile->setNested("Info.Fortune.Farmer", 0);
      $playerFile->setNested("Info.Fortune.Lumberjack", 0);
      $playerFile->setNested("Mana.Max", 100);
      $playerFile->setNested("Mana.Current", 100);
      $playerFile->setNested("Level.Miner", 1);
      $playerFile->setNested("Level.Farmer", 1);
      $playerFile->setNested("Level.Lumberjack", 1);
      $playerFile->setNested("Level.IslandLevel", 1);
      $playerFile->setNested("Xp.Miner", 0);
      $playerFile->setNested("Xp.Farmer", 0);
      $playerFile->setNested("Xp.Lumberjack", 0);
      $playerFile->setNested("Objective", "Break-Log");
      $playerFile->setNested("Bank.Money", 0);
      $playerFile->setNested("Bank.Loan", 0);
      $playerFile->setNested("Bank.Merit", 100);
      $playerFile->setNested("Bank.MaxTime", 0);
      $playerFile->setNested("Bank.Time", 0);
      $playerFile->setNested("Pet.Current", null);
      $playerFile->setNested("Pet.All", []);
      $playerFile->setNested("Profile.Menu", "GUI");
      $playerFile->setNested("Co-Op.Members", []);
      $playerFile->setNested("Co-Op.MaxMembers", 5);
      $playerFile->setNested("Co-Op.Role", "Owner");
      $playerFile->setNested("IslandSettings.Locked", false);
      $playerFile->setNested("IslandSettings.FriendsVisit", false);
      $playerFile->setNested("IslandSettings.MaxVisitors", 5);
      $playerFile->setNested("IslandSettings.CanDropItems", true);
      $playerFile->setNested("IslandSettings.Portal.Position-1", $this->getSource()->getConfigFile()->getNested("Portal.Position-1"));
      $playerFile->setNested("IslandSettings.Portal.Position-2", $this->getSource()->getConfigFile()->getNested("Portal.Position-2"));
      $playerFile->setNested("Kits.Daily.Claimed", false);
      $playerFile->setNested("Kits.Daily.MaxTime", 86400);
      $playerFile->setNested("Kits.Daily.Time", 0);
      $playerFile->setNested("Kits.Weekly.Claimed", false);
      $playerFile->setNested("Kits.Weekly.MaxTime", 604800);
      $playerFile->setNested("Kits.Weekly.Time", 0);
      $playerFile->setNested("Kits.Mothly.Claimed", false);
      $playerFile->setNested("Kits.Monthly.MaxTime", 2419200);
      $playerFile->setNested("Kits.Mothly.Time", 0);
      $playerFile->setNested("Kits.Vip.Claimed", false);
      $playerFile->setNested("Kits.Vip.MaxTime", 86400);
      $playerFile->setNested("Kits.Vip.Time", 0);
      $playerFile->setNested("Kits.YouTuber.Claimed", false);
      $playerFile->setNested("Kits.YouTuber.MaxTime", 86400);
      $playerFile->setNested("Kits.YouTuber.Time", 0);
      $playerFile->setNested("Kits.Mvp.Claimed", false);
      $playerFile->setNested("Kits.Mvp.MaxTime", 86400);
      $playerFile->setNested("Kits.Mvp.Time", 0);
      $playerFile->setNested("Kits.Sroudy.Claimed", false);
      $playerFile->setNested("Kits.Sroudy.MaxTime", 86400);
      $playerFile->setNested("Kits.Sroudy.Time", 0);
      $playerFile->setNested("Kits.Donator.Claimed", false);
      $playerFile->setNested("Kits.Donator.MaxTime", 86400);
      $playerFile->setNested("Kits.Donator.Time", 0);
      $playerFile->setNested("PotionBag", $this->getPotionBag($player));
      $playerFile->save();
    }
  }
  
  public function createIsland($player): void 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $worldPath = $this->getSource()->getDataFolder() . $this->config->get("IslandWorld"); 
    if(file_exists($worldPath))
    {
      if(!is_dir("worlds/$playerName"))
      {
        $zip = new \ZipArchive();
        $zip->open($worldPath);
        mkdir(Server::getInstance()->getDataPath() . "worlds/$playerName");
        $zip->extractTo(Server::getInstance()->getDataPath() . "worlds/$playerName");
        $zip->close();
        
        Server::getInstance()->getWorldManager()->loadWorld($playerName);
        $world = Server::getInstance()->getWorldManager()->getWorldByName($playerName);
        $world->getProvider()->getWorldData()->getCompoundTag()->setString("LevelName", $playerName);
        Server::getInstance()->getWorldManager()->unloadWorld($world); //Reloading The World
        Server::getInstance()->getWorldManager()->loadWorld($playerName);
        
        Server::getInstance()->getWorldManager()->loadWorld($playerName);
        $a_world = Server::getInstance()->getWorldManager()->getWorldByName($playerName);
        $this->spawnAssistant($a_world);
        $location = new Location(231.5, 65.0, 240.5, $a_world, 0.0, 0.0);
        $skinPath = Server::getInstance()->getDataPath() . "plugin_data/SkyIsland/model/skin/worker/Worker.png";
        $geometryPath = Server::getInstance()->getDataPath() . "plugin_data/SkyIsland/model/geometry/worker/Worker.json";
        $bytes = $this->calculateSkinBytes($skinPath);
        $skin = new Skin("Worker", $bytes, "", "geometry.worker", file_get_contents($geometryPath));
        $nbt = CompoundTag::create()
          ->setTag("Information", 
            CompoundTag::create()
              ->setInt("Level", 1)
              ->setInt("InvSize", 3)
              ->setString("Type", "Miner")
              ->setString("TargetId", "4")
              ->setString("TargetMeta", "0")
              ->setString("Owner", $playerName))
          ->setTag("Skin",
            CompoundTag::create()
              ->setString("Name", $skin->getSkinId())
              ->setByteArray("Data", $skin->getSkinData())
              ->setString('GeometryData', $skin->getGeometryData())
              );
        $entity = new Worker($location, $skin, $nbt);
        $entity->spawnToAll();
      }
    }
  }
  
  public function teleportToIsland(Player $player, int $delay): void
  {
    $playerName = $player->getName();
    $this->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
      function () use ($player, $playerName): void
      {
        if($this->hasSkyIsland($playerName))
        {
          if(!empty($this->getSource()->getPlayerFile($playerName)->get("Island")))
          {
            Server::getInstance()->getWorldManager()->loadWorld($this->getSource()->getPlayerFile($playerName)->get("Island"));
            $world = Server::getInstance()->getWorldManager()->getWorldByName($this->getSource()->getPlayerFile($playerName)->get("Island"));
            if(!is_null($world))
            {
              if($player->isOnline())
              {
                $player->teleport($world->getSpawnLocation());
              }
            }
          }
        }
      }
    ), $delay);
  }
  
  public function getPotionBag($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("PotionBag")))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("PotionBag");
      return $data;
    }else{
      $data = ["0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null"];
      return $data;
    }
  }
  
  public function setPotionBag($player, $data): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("PotionBag")))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("PotionBag", $data);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function upgradePotionBag($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(!is_null($this->getSource()->getPlayerFile($playerName)->get("PotionBag")))
    {
      $previousBag = $this->getSource()->getPlayerFile($playerName)->get("PotionBag");
      $bag = [$previousBag[0], $previousBag[1], $previousBag[2], $previousBag[3], $previousBag[4], $previousBag[5], $previousBag[6], "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null", "0:0:null"];
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("PotionBag", $bag);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function getBankMoney($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Money");
      return $data;
    }else{
      return null;
    }
  }
  
  public function addBankMoney($player, int $amount): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $money = $this->getBankMoney($player);
      $total = $money + $amount;
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Money", $total);
      $playerFile->save();
      if($this->getBankMoney($player) === 0)
      {
        if($amount >= 1)
        {
          if(!array_key_exists($playerName_1, Eventhandler::getInstance()->interest))
          {
            EventHandler::getInstance()->interest[$playerName] =  $this->getSource()->getScheduler()->scheduleRepeatingTask(new InterestTask($this->source, $playerName), 72000);
          }
        }
      }
      return true;
    }else{
      return false;
    }
  }
  
  public function transferBankMoney($player_1, $player_2, int $amount): bool
  {
    if($player_1 instanceof Player)
    {
      $playerName_1 = $player_1->getName();
    }else{
      $playerName_1 = $player_1;
    }
    if($player_2 instanceof Player)
    {
      $playerName_2 = $player_2->getName();
    }else{
      $playerName_2 = $player_2;
    }
    if($this->hasSkyIsland($playerName_2))
    {
      $money_1 = $this->getBankMoney($playerName_2);
      $total_1 = $money_1 + $amount;
      $playerFile_1 = $this->getSource()->getPlayerFile($playerName_2);
      $playerFile_1->setNested("Bank.Money", $total_1);
      $playerFile_1->save();
      $money_2 = $this->getBankMoney($playerName_1);
      $total_2 = $money_2 - $amount;
      $playerFile_2 = $this->getSource()->getPlayerFile($playerName_1);
      $playerFile_2->setNested("Bank.Money", $total_2);
      $playerFile_2->save();
      if($this->getBankMoney($player_2) === 0)
      {
        if($amount >= 1)
        {
          if(!array_key_exists($playerName_1, Eventhandler::getInstance()->interest))
          {
            EventHandler::getInstance()->interest[$playerName_2] =  $this->getSource()->getScheduler()->scheduleRepeatingTask(new InterestTask($this->source, $playerName_2), 72000);
          }
        }
      }
      if($total_2 === 0)
      {
        if(array_key_exists($playerName_1, Eventhandler::getInstance()->interest))
        {
          EventHandler::getInstance()->interest[$playerName_1]->cancel();
        }
      }
      return true;
    }else{
      return false;
    }
  }
  
  public function reduceBankMoney($player, int $amount): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $money = $this->getBankMoney($player);
      $total = $money - $amount;
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Money", $total);
      $playerFile->save();
      if($total === 0)
      {
        if(array_key_exists($playerName, Eventhandler::getInstance()->interest))
        {
          EventHandler::getInstance()->interest[$playerName]->cancel();
        }
      }
      return true;
    }else{
      return false;
    }
  }
  
  public function getLevel($player, string $object)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Level.$object")))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Level.$object");
      return $data;
    }else{
      return null;
    }
  }
  
  public function setLevel($player, string $object, int $level): void
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(is_int($this->getSource()->getPlayerFile($playerName)->getNested("Level.$object")))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Level.$object", $level);
      $playerFile->save();
    }
  }
  
  public function addLevel($player, string $object, int $level): void
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Level.$object")))
    {
      $oldLevel = $this->getSource()->getPlayerFile($playerName)->getNested("Level.$object");
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Level.$object", $oldLevel + $level);
      $playerFile->save();
    }
  }
  
  public function getXp($player, string $object)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(is_int($this->getSource()->getPlayerFile($playerName)->getNested("Xp.$object")))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Xp.$object");
      return $data;
    }else{
      return null;
    }
  }
  
  public function addXp($player, string $object, int $xp): void
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(is_int($this->getSource()->getPlayerFile($playerName)->getNested("Xp.$object")))
    {
      $oldXp = $this->getSource()->getPlayerFile($playerName)->getNested("Xp.$object");
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Xp.$object", ($oldXp + $xp));
      $playerFile->save();
    }
  }
  
  public function setXp($player, string $object, int $xp): void
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(is_int($this->getSource()->getPlayerFile($playerName)->getNested("Xp.$object")))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Xp.$object", $xp);
      $playerFile->save();
    }
  }
  
  public function isLocked($player): bool 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if($this->getSource()->getPlayerFile($playerName)->getNested("IslandSettings.Locked"))
      {
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function lockIsland($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.Locked", true);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function bazaar($inv, string $type)
  {
    for($i = 1; $i <= 18; $i++)
    {
      if($i <= 6)
      {
        $slot = $i + 10;
      }elseif($i <= 12)
      {
        $slot = $i + 13;
      }elseif($i <= 18)
      {
        $slot = $i + 16;
      }
      $inv->setItem($slot, ItemFactory::getInstance()->get(0, 0, 0));
    }
    $inv->setItem(48, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    $inv->setItem(51, ItemFactory::getInstance()->get(160, 3, 1)->setCustomName("§r §7 §r"));
    if($type === "Mining")
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(270, 0, 1)->setCustomName("§r Wooden Puckaxe §r\n§r §lCommon §r"));
      $inv->setItem(12, ItemFactory::getInstance()->get(274, 0, 1)->setCustomName("§r Stone Pickaxe §r\n§r §lCommon §r"));
      $inv->setItem(13, ItemFactory::getInstance()->get(257, 0, 1)->setCustomName("§r Iron Pickaxe §r\n§r §lCommon §r"));
      $inv->setItem(14, ItemFactory::getInstance()->get(278, 0, 1)->setCustomName("§r Diamond Pickaxe §r\n§r §lCommon §r"));
      $inv->setItem(20, ItemFactory::getInstance()->get(263, 0, 1)->setCustomName("§r Coal §r\n§r §lCommon §r"));
      $inv->setItem(21, ItemFactory::getInstance()->get(265, 0, 1)->setCustomName("§r Iron Ingot §r\n§r §lCommon §r"));
      $inv->setItem(22, ItemFactory::getInstance()->get(266, 0, 1)->setCustomName("§r Gold Ingot §r\n§r §lCommon §r"));
      $inv->setItem(23, ItemFactory::getInstance()->get(331, 0, 1)->setCustomName("§r Redstone §r\n§r §lCommon §r"));
      $inv->setItem(24, ItemFactory::getInstance()->get(351, 4, 1)->setCustomName("§r Lapis Lazuli §r\n§r §lCommon §r"));
      $inv->setItem(29, ItemFactory::getInstance()->get(388, 0, 1)->setCustomName("§r Emerald §r\n§r §lUncommon §r"));
      $inv->setItem(30, ItemFactory::getInstance()->get(264, 0, 1)->setCustomName("§r Diamond §r\n§r §lUncommon §r"));
    }elseif($type === "Fishing")
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(346, 0, 1)->setCustomName("§r Fishing Rod §r\n§r §lCommon §r"));
      $inv->setItem(12, ItemFactory::getInstance()->get(460, 0, 1)->setCustomName("§r Raw Salmon §r\n§r §lCommon §r"));
      $inv->setItem(13, ItemFactory::getInstance()->get(461, 0, 1)->setCustomName("§r Clownfish §r\n§r §lCommon §r"));
      $inv->setItem(14, ItemFactory::getInstance()->get(462, 0, 1)->setCustomName("§r Pufferfish §r\n§r §lCommon §r"));
      $inv->setItem(15, ItemFactory::getInstance()->get(349, 0, 1)->setCustomName("§r Raw Fish §r\n§r §lCommon §r"));
    }elseif($type === "Farming")
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(290, 0, 1)->setCustomName("§r Wooden Hoe §r\n§r §lCommon §r"));
      $inv->setItem(12, ItemFactory::getInstance()->get(291, 0, 1)->setCustomName("§r Stone Hoe §r\n§r §lCommon §r"));
      $inv->setItem(13, ItemFactory::getInstance()->get(292, 0, 1)->setCustomName("§r Iron Hoe §r\n§r §lCommon §r"));
      $inv->setItem(14, ItemFactory::getInstance()->get(293, 0, 1)->setCustomName("§r Diamond Hoe §r\n§r §lCommon §r"));
      $inv->setItem(20, ItemFactory::getInstance()->get(295, 0, 1)->setCustomName("§r Wheat Seeds §r\n§r §lCommon §r"));
      $inv->setItem(21, ItemFactory::getInstance()->get(458, 0, 1)->setCustomName("§r Beetroot Seeds §r\n§r §lCommon §r"));
      $inv->setItem(22, ItemFactory::getInstance()->get(361, 0, 1)->setCustomName("§r Pumpkin Seeds §r\n§r §lCommon §r"));
      $inv->setItem(23, ItemFactory::getInstance()->get(362, 0, 1)->setCustomName("§r Melon Seeds §r\n§r §lCommon §r"));
      $inv->setItem(29, ItemFactory::getInstance()->get(296, 0, 1)->setCustomName("§r Wheat §r\n§r §lCommon §r"));
      $inv->setItem(30, ItemFactory::getInstance()->get(457, 0, 1)->setCustomName("§r Beetroot §r\n§r §lCommon §r"));
      $inv->setItem(31, ItemFactory::getInstance()->get(86, 0, 1)->setCustomName("§r Pumpkin §r\n§r §lCommon §r"));
      $inv->setItem(32, ItemFactory::getInstance()->get(103, 0, 1)->setCustomName("§r Melon Block §r\n§r §lCommon §r"));
      $inv->setItem(33, ItemFactory::getInstance()->get(391, 0, 1)->setCustomName("§r Carrot §r\n§r §lCommon §r"));
      $inv->setItem(34, ItemFactory::getInstance()->get(392, 0, 1)->setCustomName("§r Potato §r\n§r §lCommon §r"));
    }elseif($type === "Lumberjack")
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(271, 0, 1)->setCustomName("§r Wooden Axe §r\n§r §lCommon §r"));
      $inv->setItem(12, ItemFactory::getInstance()->get(275, 0, 1)->setCustomName("§r Stone Axe §r\n§r §lCommon §r"));
      $inv->setItem(13, ItemFactory::getInstance()->get(258, 0, 1)->setCustomName("§r Iron Axe §r\n§r §lCommon §r"));
      $inv->setItem(14, ItemFactory::getInstance()->get(279, 0, 1)->setCustomName("§r Diamond Axe §r\n§r §lCommon §r"));
      $inv->setItem(20, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r Oak Log §r\n§r §lCommon §r"));
      $inv->setItem(21, ItemFactory::getInstance()->get(17, 1, 1)->setCustomName("§r Spruce Log §r\n§r §lCommon §r"));
      $inv->setItem(22, ItemFactory::getInstance()->get(17, 2, 1)->setCustomName("§r Birch Log §r\n§r §lCommon §r"));
      $inv->setItem(23, ItemFactory::getInstance()->get(17, 3, 1)->setCustomName("§r Jungle Log §r\n§r §lCommon §r"));
      $inv->setItem(24, ItemFactory::getInstance()->get(17, 4, 1)->setCustomName("§r Acacia Log §r\n§r §lCommon §r"));
      $inv->setItem(25, ItemFactory::getInstance()->get(17, 5, 1)->setCustomName("§r Dark Oak Log §r\n§r §lCommon §r"));
    }elseif($type === "Blocks-1")
    {
      $inv->setItem(11, ItemFactory::getInstance()->get(2, 0, 1)->setCustomName("§r Grass §r\n§r §lCommon §r"));
      $inv->setItem(12, ItemFactory::getInstance()->get(3, 0, 1)->setCustomName("§r Dirt §r\n§r §lCommon §r"));
      $nextPage = ItemFactory::getInstance()->get(160, 5, 1);
      $inv->setItem(51, $nextPage->setNamedTag($nextPage->getNamedTag()->setString("NextPage", "Blocks-2"))->setCustomName("§r §aNext Page §r"));
    }elseif($type === "Blocks-2")
    {
      $priviousPage = ItemFactory::getInstance()->get(160, 4, 1);
      $inv->setItem(48, $priviousPage->setNamedTag($priviousPage->getNamedTag()->setString("PriviousPage", "Blocks-1"))->setCustomName("§r §ePrivious Page §r"));
    }elseif($type === "Custom-Items")
    {
      $enchantmentId = StringToEnchantmentParser::getInstance()->parse("unbreaking"); 
      $enchantment = new EnchantmentInstance($enchantmentId, 1);
      $inv->setItem(11, ItemFactory::getInstance()->get(264, 0, 1)->setCustomName("§r §bEnchanted Diamond §r\n§r §7 §r\n§r §l§cRare §r")->addEnchantment($enchantment));
      $inv->setItem(12, ItemFactory::getInstance()->get(296, 0, 1)->setCustomName("§r §eEnchanted Wheat §r\n§r §7 §r\n§r §l§cRare §r")->addEnchantment($enchantment));
      $inv->setItem(13, ItemFactory::getInstance()->get(17, 0, 1)->setCustomName("§r §6Enchanted Oak Log §r\n§r §7 §r\n§r §l§cRare §r")->addEnchantment($enchantment));
      $inv->setItem(14, ItemFactory::getInstance()->get(369, 0, 1)->setCustomName("§r §eWand §r\n§r §7 §r\n§r §7Used To Craft Wands §r\n§r §7 §r\n§r §lUncommon §r")->addEnchantment($enchantment));
      $inv->setItem(15, ItemFactory::getInstance()->get(369, 0, 1)->setCustomName("§r §eBuilder Wand §r\n§r §7 §r\n§r §7- Left-Click To Open GUI §r\n§r §7- Right-Click To Use §r\n§r §7 §r\n§r §l§cRare §r")->addEnchantment($enchantment));
    }
  }
  
  public function getOffers()
  {
    $array = [];
    foreach(scandir($this->getSource()->getDataFolder() . "bazaar/offers") as $key => $file)
    {
      if(is_file($this->getSource()->getDataFolder() . "bazaar/offers/$file"))
      {
        $array[] = $file;
      }
    }
    return $array;
  }
  
  public function getOrders()
  {
    $array = [];
    foreach(scandir($this->getSource()->getDataFolder() . "bazaar/orders") as $key => $file)
    {
      if(is_file($this->getSource()->getDataFolder() . "bazaar/orders/$file"))
      {
        $array[] = $file;
      }
    }
    return $array;
  }
  
  public function getCheapestOffer($item, $player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $cheapest = PHP_INT_MAX;
    foreach($this->getOffers() as $offer)
    {
      $file = new Config($this->getSource()->getDataFolder() . "bazaar/offers/$offer", Config::YAML, [
        ]);
      $seller = $file->getNested("Offer.Seller");
      $ItemData = "{$item->getId()}:{$item->getMeta()}:{$item->getName()}";
      $given = $file->getNested("Offer.Item.Count") - $file->getNested("Offer.Sold");
			$FileData = ((string) $file->getNested("Offer.Item.Id") . ":" . $file->getNested("Offer.Item.Meta") . ":" . $file->getNested("Offer.Item.Name"));
			if($ItemData === $FileData && $seller !== $playerName && $given !== 0)
			{
        $price = $file->getNested("Offer.Price");
        if(!is_int($cheapest))
         {
          if($price < $cheapest->getNested("Offer.Price"))
          {
            $cheapest = $file->getNested("Offer.Price");
          }
        }else{
          if($price < $cheapest)
          {
            $cheapest = $file->getNested("Offer.Price");
          }
        }
      }
    }
    return $cheapest;
  }
  
  public function getExpensiveOrder($item, $player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $mostExpensive = 0;
    foreach($this->getOrders() as $order)
    {
      $file = new Config($this->getSource()->getDataFolder() . "bazaar/orders/$order", Config::YAML, [
        ]);
      $customer = $file->getNested("Order.Customer");
			$ItemData = "{$item->getId()}:{$item->getMeta()}:{$item->getName()}";
      $needed = $file->getNested("Order.Item.Count") - $file->getNested("Order.Bought");
			$FileData = ((string) $file->getNested("Order.Item.Id") . ":" . $file->getNested("Order.Item.Meta") . ":" . $file->getNested("Order.Item.Name"));
			if($ItemData === $FileData && $customer !== $playerName && $needed !== 0)
			{
        $price = $file->getNested("Order.Price");
        if(!is_int($mostExpensive))
        {
          if($price > $mostExpensive->getNested("Order.Price"))
          {
            $mostExpensive = $file->getNested("Order.Price");
          }
        }else{
          if($price > $mostExpensive)
          {
            $mostExpensive = $file->getNested("Order.Price");
          }
        }
      }
    }
    return $mostExpensive;
  }
  
  public function getOffersRanking($item)
  {
    $stats = [];
    foreach(glob($this->getSource()->getDataFolder() . "bazaar/offers/*.yml") as $offer)
    {
			$file = new Config($offer);
			$ItemData = "{$item->getId()}:{$item->getMeta()}:{$item->getName()}";
			$FileData = ((string) $file->getNested("Offer.Item.Id") . ":" . $file->getNested("Offer.Item.Meta") . ":" . $file->getNested("Offer.Item.Name"));
			if($ItemData === $FileData)
      {
        $stats[$this->randomString(50, $stats)] = array("Price" => $file->getNested("Offer.Price"), "Count" => ($file->getNested("Offer.Item.Count") - $file->getNested("Offer.Sold")));
			}
		}
		if($stats !== [])
		{
      asort($stats);
      $finalRankings = "";
      $i = 1;
      foreach($stats as $a_key => $array)
      {
        $count = $array["Count"];
        $number = $array["Price"];
        $same = 1;
        $spForm = "offer";
        foreach($stats as $b_key => $array)
        {
          if($number === $array["Price"] && $a_key !== $b_key)
          {
            $same++;
            $spForm = "offers";
            $count += $array["Count"];
            unset($stats[$b_key]);
          }
        }
        $finalRankings .= "§r§7- §e" . $number . " §7each | §a$count"."§7x in §r$same §7$spForm §r\n";
        if($i >= 8)
        {
          return $finalRankings;
        }
        if(count($stats) <= $i)
        {
          return $finalRankings;
        }
        $i++;
      }
		}
  }
  
  public function getOrdersRanking(Item $item)
  {
    $stats = [];
    foreach(glob($this->getSource()->getDataFolder() . "bazaar/orders/*.yml") as $order)
    {
			$file = new Config($order);
			$ItemData = "{$item->getId()}:{$item->getMeta()}:{$item->getName()}";
			$FileData = ((string) $file->getNested("Order.Item.Id") . ":" . $file->getNested("Order.Item.Meta") . ":" . $file->getNested("Order.Item.Name"));
			$needed = $file->getNested("Order.Item.Count") - $file->getNested("Order.Bought");
			if($ItemData === $FileData && $needed > 0)
			{
        $stats[$this->randomString(50, $stats)] = array("Price" => $file->getNested("Order.Price"), "Count" => ($file->getNested("Order.Item.Count") - $file->getNested("Order.Bought")));
			}
    }
		if($stats !== [])
		{
      arsort($stats);
      $finalRankings = "";
      $i = 1;
      foreach($stats as $a_key => $array)
      {
        $count = $array["Count"];
        $number = $array["Price"];
        $same = 1;
        $spForm = "order";
        foreach($stats as $b_key => $array)
        {
          if($number === $array["Price"] && $a_key !== $b_key)
          {
            $same++;
            $spForm = "orders";
            $count += $array["Count"];
            unset($stats[$b_key]);
          }
        }
        $finalRankings .= "§r§7- §e" . $number . " §7each | §a$count"."§7x in §r$same §7$spForm §r\n";
        if($i >= 8)
        {
          return $finalRankings;
        }
        if(count($stats) <= $i)
        {
          return $finalRankings;
        }
        $i++;
      }
		}
  }
  
  public function createOrder(string $customer, Item $item, int $price)
  {
    $file_name = $this->randomString(50, $this->getSource()->getDataFolder() . "bazaar/orders", "yml");
    $this->getSource()->saveResource("bazaar/orders/$file_name" . ".yml");
    $file = new Config($this->getSource()->getDataFolder() . "bazaar/orders/$file_name" . ".yml", Config::YAML, [
      ]);
    $file->setNested("Order.Customer", $customer);
    $file->setNested("Order.Price", $price);
    $file->setNested("Order.Item.Id", $item->getId());
    $file->setNested("Order.Item.Name", $item->getName());
    $file->setNested("Order.Item.Meta", $item->getMeta());
    $file->setNested("Order.Item.Count", $item->getCount());
    $file->setNested("Order.Item.Nbt", serialize(clone $item->getNamedTag()));
    $file->setNested("Order.Bought", 0);
    $file->save();
    $this->updateBazaar();
  }
  
  public function createOffer(string $seller, Item $item, int $price)
  {
    $file_name = $this->randomString(50, $this->getSource()->getDataFolder() . "bazaar/offers", "yml");
    $this->getSource()->saveResource("bazaar/offers/$file_name" . ".yml");
    $file = new Config($this->getSource()->getDataFolder() . "bazaar/offers/$file_name" . ".yml", Config::YAML, [
      ]);
    $file->setNested("Offer.Seller", $seller);
    $file->setNested("Offer.Price", $price);
    $file->setNested("Offer.Item.Id", $item->getId());
    $file->setNested("Offer.Item.Name", $item->getName());
    $file->setNested("Offer.Item.Meta", $item->getMeta());
    $file->setNested("Offer.Item.Count", $item->getCount());
    $file->setNested("Offer.Item.Nbt", serialize(clone $item->getNamedTag()));
    $file->setNested("Offer.Sold", 0);
    $file->save();
    $this->updateBazaar();
  }
  
  public function updateBazaar(): void
  {
    foreach($this->getOrders() as $order)
    {
      $file_1 = new Config($this->getSource()->getDataFolder() . "bazaar/orders/$order");
      $order_price = $file_1->getNested("Order.Price");
      $customer = $file_1->getNested("Order.Customer");
      $needed = $file_1->getNested("Order.Item.Count") - $file_1->getNested("Order.Bought");
      if($needed > 0)
      {
        foreach($this->getOffers() as $offer)
        {
          $file_2 = new Config($this->getSource()->getDataFolder() . "bazaar/offers/$offer");
          $FileData_1 = $file_1->getNested("Order.Item.Id") . ":" . $file_1->getNested("Order.Item.Meta") . ":" . $file_1->getNested("Order.Item.Name");
          $FileData_2 = $file_2->getNested("Offer.Item.Id") . ":" . $file_2->getNested("Offer.Item.Meta") . ":" . $file_2->getNested("Offer.Item.Name");
          if($FileData_1 === $FileData_2)
          {
            $offer_price = $file_2->getNested("Offer.Price");
            $seller = $file_2->getNested("Offer.Seller");
            if($order_price === $offer_price && $customer !== $seller)
            {
              $given = $file_2->getNested("Offer.Item.Count") - $file_2->getNested("Offer.Sold");
              $offer_extra = 0;
              $order_left = 0;
              if($given > 0)
              {
                if($given > $needed)
                {
                  $offer_extra = $given - $needed;
                }elseif($needed > $given)
                {
                  $order_left = $needed - $given;
                }
                if($order_left > 0)
                {
                  $file_1->setNested("Order.Bought", $given + $file_1->getNested("Order.Bought"));
                  $file_1->save();
                  $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
                  $economy->addToPlayerBalance($file_2->getNested("Offer.Seller"), $offer_price * $given);
                  unlink($this->getSource()->getDataFolder() . "bazaar/offers/$offer");
                  $seller = Server::getInstance()->getPlayerExact($file_2->getNested("Offer.Seller"));
                  if($seller instanceof Player)
                  {
                    $seller->sendMessage("§eBazaar §6>> §ayour offer of §e" . $file_2->getNested("Offer.Item.Name") . " §ahas been sold");
                  }
                }elseif($offer_extra > 0)
                {
                  $file_2->setNested("Offer.Sold", $needed + $file_2->getNested("Offer.Sold"));
                  $file_2->save();
                  $file_1->setNested("Order.Bought", $file_1->getNested("Order.Item.Count"));
                  $file_1->save();
                  $customer = Server::getInstance()->getPlayerExact($file_1->getNested("Order.Customer"));
                  if($customer instanceof Player)
                  {
                    $customer->sendMessage("§eBazaar §6>> §ayour order of §e" . $file_2->getNested("Offer.Item.Name") . " §ahas been filled");
                    $customer->sendMessage("§eBazaar §6>> §ayou can collect it via manage order in bazaar");
                  }
                  $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
                  $economy->addToPlayerBalance($file_2->getNested("Offer.Seller"), $offer_price * $needed);
                  break;
                }else{
                  $file_1->setNested("Order.Bought", $file_1->getNested("Order.Item.Count"));
                  $file_1->save();
                  $customer = Server::getInstance()->getPlayerExact($file_1->getNested("Order.Customer"));
                  if($customer instanceof Player)
                  {
                    $customer->sendMessage("§eBazaar §6>> §ayour order of §e" . $file_2->getNested("Offer.Item.Name") . " §ahas been filled");
                    $customer->sendMessage("§eBazaar §6>> §ayou can collect it via manage order in bazaar");
                  }
                  $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
                  $economy->addToPlayerBalance($file_2->getNested("Offer.Seller"), $offer_price * $given);
                  unlink($this->getSource()->getDataFolder() . "bazaar/offers/$offer");
                  $seller = Server::getInstance()->getPlayerExact($file_2->getNested("Offer.Seller"));
                  if($seller instanceof Player)
                  {
                    $seller->sendMessage("§eBazaar §6>> §ayour offer of §e" . $file_2->getNested("Offer.Item.Name") . " §ahas been sold");
                  }
                  break;
                }
              }
            }
          }
        }
      }
    }
  }
  
  public function unlockIsland($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.Locked", false);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function getMaxVisitors($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("IslandSettings.MaxVisitors");
      return $data;
    }else{
      return null;
    }
  }
  
  public function setMaxVisitors($player, int $visitors): bool 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.MaxVisitors", $visitors);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function setIslandSpawn($player): bool 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if($player->getLocation()->getWorld()->getFolderName() === $this->getSource()->getPlayerFile($playerName)->get("Island"))
      {
        $player->getWorld()->setSpawnLocation(new Vector3($player->getLocation()->x, $player->getLocation()->y, $player->getLocation()->z));
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function getCanDropItems($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("IslandSettings.CanDropItems");
      return $data;
    }else{
      return null;
    }
  }
  
  public function setCanDropItems($player, bool $canDrop): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.CanDropItems", $canDrop);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function getFriendsVisit($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("IslandSettings.FriendsVisit");
      return $data;
    }else{
      return null;
    }
  }
  
  public function setFriendsVisit($player, bool $canVisit): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.FriendsVisit", $canVisit);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function fastTravel(Player $player, string $location): bool
  {
    if(!is_null($this->config->getNested("FastTravel.$location")))
    {
      Server::getInstance()->getWorldManager()->loadWorld($this->config->getNested("FastTravel.$location")[3]);
      $world = Server::getInstance()->getWorldManager()->getWorldByName($this->config->getNested("FastTravel.$location")[3]);
      if($world !== null)
      {
        $pos = $this->config->getNested("FastTravel.$location");
        $player->teleport(new Location($pos[0], $pos[1], $pos[2], $world, 0, 0));
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function sendLevelUpMessage(Player $player, string $object): void
  {
    if($object === "Miner")
    {
      $level = $this->getLevel($player, "Miner");
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $rewards = [];
      if($level === 2)
      {
        $rewards = ["§eMine FastTravel", "§e+ 1,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 1000);
      }elseif($level === 3)
      {
        $rewards = ["§eCoal Worker Recipe", "§ePure Coal Recipe", "§e+ 2,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 2000);
      }elseif($level === 4)
      {
        $rewards = ["§eIron Worker Recipe", "§ePure Iron Recipe", "§e+ 3,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 3000);
      }elseif($level === 5)
      {
        $rewards = ["§eMiner Armor Recipe", "§eLapis Worker Recipe", "§ePure Lapis Recipe", "§e+ 3,600 coins"];
        $economy->addToPlayerBalance($player->getName(), 3600);
      }elseif($level === 6)
      {
        $rewards = ["§eGold Worker Recipe", "§ePure Gold Recipe", "§e+ 5,250 coins"];
        $economy->addToPlayerBalance($player->getName(), 5250);
      }elseif($level === 7)
      {
        $rewards = ["§eMiner Pickaxe Recipe", "§eEmerald Worker Recipe", "§ePure Emerald Recipe", "§e+ 6,900 coins"];
        $economy->addToPlayerBalance($player->getName(), 6900);
      }elseif($level === 8)
      {
        $rewards = ["§eBazaar (33.3%)", "§eDiamond Worker Recipe", "§ePure Diamond Recipe", "§e+ 7,200 coins"];
        $economy->addToPlayerBalance($player->getName(), 7200);
      }elseif($level === 9)
      {
        $rewards = ["§eDiamond Spreading Recipe", "§e+ 8,400 coins"];
        $economy->addToPlayerBalance($player->getName(), 8400);
      }elseif($level === 10)
      {
        $rewards = ["§eCompactor Recipe", "§e+ 9,134 coins"];
        $economy->addToPlayerBalance($player->getName(), 9134);
      }elseif($level === 11)
      {
        $rewards = ["§eRock Pet", "§e+ 10,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 10000);
      }elseif($level === 12)
      {
        $rewards = ["§eMining Bag", "§e+ 11,278 coins"];
        $economy->addToPlayerBalance($player->getName(), 11278);
      }elseif($level === 13)
      {
        $rewards = ["§eMithril Crystal Recipe", "§e+ 12,450 coins"];
        $economy->addToPlayerBalance($player->getName(), 12450);
      }elseif($level === 14)
      {
        $rewards = ["§eMithril Pickaxe Recipe", "§e+ 13,451 coins"];
        $economy->addToPlayerBalance($player->getName(), 13451);
      }elseif($level === 15)
      {
        $rewards = ["§ePower Scroll Recipe", "§e+ 13,987 coins"];
        $economy->addToPlayerBalance($player->getName(), 13987);
      }elseif($level === 16)
      {
        $rewards = ["§ePure Mithril Recipe", "§e+ 14,786 coins"];
        $economy->addToPlayerBalance($player->getName(), 14786);
      }elseif($level === 17)
      {
        $rewards = ["§eReforging", "§e+ 15,980 coins"];
        $economy->addToPlayerBalance($player->getName(), 15980);
      }elseif($level === 18)
      {
        $rewards = ["§eSlimy Pet", "§e+ 16,869 coins"];
        $economy->addToPlayerBalance($player->getName(), 16869);
      }elseif($level === 19)
      {
        $rewards = ["§eTreasure Finder", "§e+ 17,960 coins"];
        $economy->addToPlayerBalance($player->getName(), 17960);
      }elseif($level === 20)
      {
        $rewards = ["§eWand Recipe", "§e+ 19,007 coins"];
        $economy->addToPlayerBalance($player->getName(), 19007);
      }elseif($level === 21)
      {
        $rewards = ["§eWand Of Mending Recipe", "§eWand Of Healing Recipe", "§e+ 20,170 coins"];
        $economy->addToPlayerBalance($player->getName(), 20170);
      }elseif($level === 22)
      {
        $rewards = ["§eLapis Armor Recipe", "§e+ 21,164 coins"];
        $economy->addToPlayerBalance($player->getName(), 21164);
      }elseif($level === 23)
      {
        $rewards = ["§eEmerald Armor Recipe", "§e+ 22,019 coins"];
        $economy->addToPlayerBalance($player->getName(), 22019);
      }elseif($level === 24)
      {
        $rewards = ["§eRedstone Armor Recipe", "§e+ 22,969 coins"];
        $economy->addToPlayerBalance($player->getName(), 22969);
      }elseif($level === 25)
      {
        $rewards = ["§eMining Xp Boost", "§e+ 23,868 coins"];
        $economy->addToPlayerBalance($player->getName(), 23868);
      }elseif($level === 26)
      {
        $rewards = ["§eGrappling Hook Recipe", "§e+ 24,907 coins"];
        $economy->addToPlayerBalance($player->getName(), 24907);
      }elseif($level === 27)
      {
        $rewards = ["§ePortal Of Deep Cavans Recipe", "§eHarden Diamond Recipe", "§eHarden Diamond Armor Recipe", "§e+ 26,003 coins"];
        $economy->addToPlayerBalance($player->getName(), 26003);
      }elseif($level === 28)
      {
        $rewards = ["§eDragon Armor Recipe", "§e+ 27,024 coins"];
        $economy->addToPlayerBalance($player->getName(), 27024);
      }elseif($level === 29)
      {
        $rewards = ["§eFire Talisman Recipe", "§eMagnetic Talisman Recipe", "§e+ 27,800 coins"];
        $economy->addToPlayerBalance($player->getName(), 27800);
      }elseif($level === 30)
      {
        $rewards = ["§eMagical Water Bucket Recipe", "§eMagical Lava Bucket Recipe", "§eTalisman Of Power Recipe", "§e+ 28,860 coins"];
        $economy->addToPlayerBalance($player->getName(), 28860);
      }elseif($level === 31)
      {
        $rewards = ["§eGravity Talisman Recipe", "§eMithril Pet", "§e+ 30,660 coins"];
        $economy->addToPlayerBalance($player->getName(), 30620);
      }elseif($level === 32)
      {
        $rewards = ["§eSuperior Armor Recipe", "§eGod Potion Recipe", "§e+ 32,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 32000);
      }
      $player->sendMessage("§b--------------------------------");
      $player->sendMessage("§r      §b§lSKILL LEVEL UP      §r");
      $player->sendMessage("§r         §l§bMining §r§8" . $level - 1 . " ➷ $level §r");
      $player->sendMessage("§r          §l§aREWARDS         §r");
      foreach($rewards as $reward)
      {
        $player->sendMessage("§r        $reward        §r");
      }
      $player->sendMessage("§b--------------------------------");
    }elseif($object === "Farmer")
    {
      $level = $this->getLevel($player, "Farmer");
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $rewards = [];
      if($level === 2)
      {
        $rewards = ["§aFarm FastTravel\n§e2,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 2000);
      }
      $player->sendMessage("§b--------------------------------");
      $player->sendMessage("§r      §b§lSKILL LEVEL UP      §r");
      $player->sendMessage("§r         §l§eFarming §r§8" . $level - 1 . " ➷ $level §r");
      $player->sendMessage("§r          §l§aREWARDS         §r");
      foreach($rewards as $reward)
      {
        $player->sendMessage("§r        $reward        §r");
      }
      $player->sendMessage("§b--------------------------------");
    }elseif($object === "Lumberjack")
    {
      $level = $this->getLevel($player, "Lumberjack");
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $rewards = [];
      if($level === 2)
      {
        $rewards = ["§aSmall PotionBag\n§e2,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 2000);
        if($this->getObjective($player) === "Unlock-PotionBag")
        {
          $nextObjective = $this->getNextObjective($player);
          $this->setObjective($player, $nextObjective);
        }
      }elseif($level === 3)
      {
        $rewards = ["§aForest FastTravel\n§e3,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 3000);
      }elseif($level === 4)
      {
        $rewards = ["§aLumberjack Worker\n§e4,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 4000);
      }elseif($level === 5)
      {
        $rewards = ["§aForest Portal\n§e5,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 5000);
      }elseif($level === 6)
      {
        $rewards = ["§aLarge PotionBag\n§e6,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 6000);
        $this->upgradePotionBag($player);
      }elseif($level === 7)
      {
        $rewards = ["§e7,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 7000);
      }elseif($level === 8)
      {
        $rewards = ["§e8,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 8000);
      }elseif($level === 9)
      {
        $rewards = ["§e9,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 9000);
      }elseif($level === 10)
      {
        $rewards = ["§e10,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 10000);
      }elseif($level === 11)
      {
        $rewards = ["§e11,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 11000);
      }elseif($level === 12)
      {
        $rewards = ["§e12,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 12000);
      }elseif($level === 13)
      {
        $rewards = ["§e13,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 13000);
      }elseif($level === 14)
      {
        $rewards = ["§e14,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 14000);
      }elseif($level === 15)
      {
        $rewards = ["§e15,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 15000);
      }elseif($level === 16)
      {
        $rewards = ["§e16,000 coins"];
        $economy->addToPlayerBalance($player->getName(), 16000);
      }
      $player->sendMessage("§b--------------------------------");
      $player->sendMessage("§r      §b§lSKILL LEVEL UP      §r");
      $player->sendMessage("§r      §l§6Lumberjack §r§8" . $level - 1 . " ➷ $level §r");
      $player->sendMessage("§r          §l§aREWARDS         §r");
      foreach($rewards as $reward)
      {
        $player->sendMessage("§r        $reward        §r");
      }
      $player->sendMessage("§b--------------------------------");
    }
  }
  
  public function getRarity($item)
  {
    if($item instanceof Item)
    {
      $itemId = $item->getId();
    }else{
      $itemId = $item;
    }
    $array_1 = array(264, 388, 57, 133, 276, 277, 278, 279, 293);
    $array_2 = array();
    $array_3 = array();
    $array_4 = array();
    if(in_array($itemId, $array_1))
    {
      return "§lUncommon";
    }elseif(in_array($itemId, $array_2))
    {
      return "§l§cRare";
    }elseif(in_array($itemId, $array_3))
    {
      return "§l§5Mythic";
    }elseif(in_array($itemId, $array_4))
    {
      return "§l§6Legendary";
    }else{
      return "§lCommon";
    }
  }
  
  public function CoOpPromote(string $victimName): bool
  {
    $role = $this->getCoOpRole($victimName);
    if($role !== "Owner" && $role !== "Co-Owner")
    {
      $role = $this->getCoOpRole($victimName);
      $promotedRole = array(
        "Builder" => "Member",
        "Member" => "Senior-Member",
        "Senior-Member" => "Co-Owner"
        );
      $new_role = $promotedRole[$role];
      $file = $this->getSource()->getPlayerFile($victimName);
      $file->setNested("Co-Op.Role", $new_role);
      $file->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function CoOpDemote(string $victimName)
  {
    $role = $this->getCoOpRole($victimName);
    if($role !== "Owner" && $role !== "Builder")
    {
      $role = $this->getCoOpRole($victimName);
      $demotedRole = array(
        "Member" => "Builder",
        "Senior-Member" => "Member",
        "Co-Owner" => "Senior-Member"
        );
      $new_role = $demotedRole[$role];
      $file = $this->getSource()->getPlayerFile($victimName);
      $file->setNested("Co-Op.Role", $new_role);
      $file->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function addCoOp($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $victimRealName = $this->getRealCoOpName($player, $victimName);
    $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
    $isCoOp = $this->isCoOp($playerName, $victimRealName);
    if($this->hasSkyIsland($playerName))
    {
      if($hasRequest && !$isCoOp)
      {
        if($victimRealName !== "" && $playerName !== $victimRealName)
        {
          $members = count($this->getMembers($player));
          $maxMembers = $this->getMaxMembers($player);
          if($members < $maxMembers)
          {
            $this->removeCoOpRequest($playerName, $victimRealName);
            $playerFile = $this->getSource()->getPlayerFile($playerName);
            $victimFile = $this->getSource()->getPlayerFile($victimRealName);
            $playerFile->setNested("Island", $victimFile->get("Island"));
            $playerFile->setNested("Co-Op.Role", "Member");
            $playerFile->save();
            $world = $this->getSource()->getServer()->getWorldManager()->getWorldByName($playerName);
            $this->getSource()->getServer()->getWorldManager()->unloadWorld($world);
            $this->deleteDirectory($this->getSource()->getServer()->getDataPath() . "worlds/$playerName");
            $victim = Server::getInstance()->getPlayerByPrefix($playerName);
            if($victim instanceof Player)
            {
              $this->teleportToIsland($victim, 1);
            }
            foreach(scandir($this->getSource()->getDataFolder() . "players") as $key => $file)
            {
              if(is_file($this->getSource()->getDataFolder() . "players/$file"))
              {
                $playerFile = new Config($this->getSource()->getDataFolder() . "players/$file", Config::YAML, [
                  ]);
                if($playerFile->get("Island") === $victimFile->get("Island"))
                {
                  if($playerFile->getNested("Co-Op.Role") === "Owner" || $playerFile->getNested("Co-Op.Role") === "Co-Owner")
                  {
                    $members = $playerFile->getNested("Co-Op.Members");
                    $members[] = $playerName;
                    $playerFile->setNested("Co-Op.Members", $members);
                    $playerFile->save();
                  }
                }
              }
            }
            return true;
          }else{
            return false;
          }
        }else{
          return false;
        }
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function addFriend($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasFriendRequest($playerName, $victimName);
    $victimRealName = $this->getRealRequestName($player, $victimName);
    $isFriend = $this->isFriend($playerName, $victimRealName);
    if($this->hasSkyIsland($playerName))
    {
      if($hasRequest && !$isFriend)
      {
        if($victimRealName !== "" && $playerName !== $victimRealName)
        {
          $this->removeFriendRequest($playerName, $victimRealName);
          $playerFriends = $this->getFriends($playerName);
          $playerFriends[] = $victimRealName;
          $victimFriends = $this->getFriends($victimRealName);
          $victimFriends[] = $playerName;
          $playerFile = $this->getSource()->getPlayerFile($playerName);
          $victimFile = $this->getSource()->getPlayerFile($victimRealName);
          $playerFile->setNested("Friends", $playerFriends);
          $victimFile->setNested("Friends", $victimFriends);
          $playerFile->save();
          $victimFile->save();
          return true;
        }else{
          return false;
        }
        if($this->getObjective($player) === "Make-Friend")
        {
          $nextObjective = $this->getNextObjective($player);
          $this->setObjective($player, $nextObjective);
        }
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function removeCoOp(string $victimName): bool
  {
    if($this->hasSkyIsland($victimName))
    {
      if($victimName !== "")
      {
        $victimFile = $this->getSource()->getPlayerFile($victimName);
        foreach(scandir($this->getSource()->getDataFolder() . "players") as $key => $file)
        {
          if(is_file($this->getSource()->getDataFolder() . "players/$file"))
          {
            $playerFile = new Config($this->getSource()->getDataFolder() . "players/$file", Config::YAML, [
              ]);
            if($playerFile->get("Island") === $victimFile->get("Island"))
            {
              if($playerFile->getNested("Co-Op.Role") === "Owner" || $playerFile->getNested("Co-Op.Role") === "Co-Owner")
              {
                $members = $playerFile->getNested("Co-Op.Members");
                $new_Members = $this->removeKeyFromArray($members, $victimName);
                $playerFile->setNested("Co-Op.Members", $new_Members);
                $playerFile->save();
              }
            }
          }
        }
        $victimFile->setNested("Island", "$victimName");
        $victimFile->setNested("Co-Op.Role", "Owner");
        $victimFile->setNested("Co-Op.Members", []);
        $victimFile->save();
        $this->createIsland($victimName);
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function removeFriend($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $isFriend = $this->isFriend($playerName, $victimName);
    if($this->hasSkyIsland($playerName))
    {
      if($isFriend)
      {
        $victimRealName = $this->getRealFriendName($playerName, $victimName);
        if($victimRealName !== "" && $playerName !== $victimRealName)
        {
          $array1 = $this->getFriends($playerName);
          $array2 = $this->getFriends($victimRealName);
          $playerFriends = $this->removeKeyFromArray($array1, $victimRealName);
          $victimFriends = $this->removeKeyFromArray($array2, $playerName);
          $playerFile = $this->getSource()->getPlayerFile($playerName);
          $victimFile = $this->getSource()->getPlayerFile($victimRealName);
          $playerFile->setNested("Friends", $playerFriends);
          $victimFile->setNested("Friends", $victimFriends);
          $playerFile->save();
          $victimFile->save();
          return true;
        }else{
          return false;
        }
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function addFriendRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasFriendRequest($playerName, $victimName);
    $isFriend = $this->isFriend($playerName, $victimName);
    if(!$hasRequest && !$isFriend)
    {
      if($playerName !== $victimName)
      {
        $requests = [];
        $requests = $this->getFriendRequests($playerName);
        $requests[] = $victimName;
        $playerFile = $this->getSource()->getPlayerFile($playerName);
        $playerFile->setNested("FriendRequests", $requests);
        $playerFile->save();
        $this->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
          function () use ($playerName, $victimName): void 
          {
            $hasRequest = $this->hasFriendRequest($playerName, $victimName);
            $isFriend = $this->isFriend($playerName, $victimName);
            if($hasRequest && !$isFriend && $playerName !== $victimName)
            {
              $this->removeFriendRequest($playerName, $victimName);
              $victim = Server::getInstance()->getPlayerExact($victimName);
              if($victim instanceof Player)
              {
                $victim->sendMessage("§cyour friend request to §e$playerName §chas expired");
              }
            }
          }
        ), 20 * 60);
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function removeFriendRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasFriendRequest($playerName, $victimName);
    if($this->hasSkyIsland($playerName))
    {
      if($hasRequest)
      {
        if(count($this->getSource()->getPlayerFile($playerName)->get("FriendRequests")) > 1)
        {
          $victimRealName = $this->getRealRequestName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $array = $this->getFriendRequests($playerName);
            $requests = $this->removeKeyFromArray($array, $victimRealName);
            $playerFile = $this->getSource()->getPlayerFile($playerName);
            $playerFile->setNested("FriendRequests", $requests);
            $playerFile->save();
            return true;
          }else{
            return false;
          }
        }else{
          $victimRealName = $this->getRealRequestName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $requests = $this->getFriendRequests($playerName);
            if($requests[0] === $victimRealName)
            {
              $playerFile = $this->getSource()->getPlayerFile($playerName);
              $playerFile->removeNested("FriendRequests");
              $playerFile->save();
              return true;
            }else{
              return false;
            }
          }else{
            return false;
          }
        }
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function addTradeRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasTradeRequest($playerName, $victimName);
    if(!$hasRequest)
    {
      if($playerName !== $victimName)
      {
        $requests = [];
        $requests = $this->getTradeRequests($playerName);
        $requests[] = $victimName;
        $playerFile = $this->getSource()->getPlayerFile($playerName);
        $playerFile->setNested("TradeRequests", $requests);
        $playerFile->save();
        $this->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
          function () use ($playerName, $victimName): void 
          {
            $hasRequest = $this->hasTradeRequest($playerName, $victimName);
            if($hasRequest && $playerName !== $victimName)
            {
              $this->removeTradeRequest($playerName, $victimName);
              $victim = Server::getInstance()->getPlayerExact($victimName);
              if($victim instanceof Player)
              {
                $victim->sendMessage("§cyour trade request to §e$playerName §chas expired");
              }
            }
          }
        ), 20 * 60);
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function addCoOpRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
    if(!$hasRequest)
    {
      if($playerName !== $victimName)
      {
        $requests = [];
        $requests = $this->getCoOpRequests($playerName);
        $requests[] = $victimName;
        $playerFile = $this->getSource()->getPlayerFile($playerName);
        $playerFile->setNested("Co-Op.Requests", $requests);
        $playerFile->save();
        $this->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
          function () use ($playerName, $victimName): void 
          {
            $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
            if($hasRequest && $playerName !== $victimName)
            {
              $this->removeCoOpRequest($playerName, $victimName);
              $victim = Server::getInstance()->getPlayerExact($victimName);
              if($victim instanceof Player)
              {
                $victim->sendMessage("§cyour co-op request to §e$playerName §chas expired");
              }
            }
          }
        ), 20 * 60);
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function removeTradeRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasTradeRequest($playerName, $victimName);
    if($this->hasSkyIsland($playerName))
    {
      if($hasRequest)
      {
        if(count($this->getSource()->getPlayerFile($playerName)->get("TradeRequests")) > 1)
        {
          $victimRealName = $this->getRealTradeName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $array = $this->getTradeRequests($playerName);
            $requests = $this->removeKeyFromArray($array, $victimRealName);
            $playerFile = $this->getSource()->getPlayerFile($playerName);
            $playerFile->setNested("TradeRequests", $requests);
            $playerFile->save();
            return true;
          }else{
            return false;
          }
        }else{
          $victimRealName = $this->getRealTradeName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $requests = $this->getTradeRequests($playerName);
            if($requests[0] === $victimRealName)
            {
              $playerFile = $this->getSource()->getPlayerFile($playerName);
              $playerFile->removeNested("TradeRequests");
              $playerFile->save();
              return true;
            }else{
              return false;
            }
          }else{
            return false;
          }
        }
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function removeCoOpRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
    if($this->hasSkyIsland($playerName))
    {
      if($hasRequest)
      {
        if(count($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")) > 1)
        {
          $victimRealName = $this->getRealTradeName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $array = $this->getTradeRequests($playerName);
            $requests = $this->removeKeyFromArray($array, $victimRealName);
            $playerFile = $this->getSource()->getPlayerFile($playerName);
            $playerFile->setNested("Co-Op.Requests", $requests);
            $playerFile->save();
            return true;
          }else{
            return false;
          }
        }else{
          $victimRealName = $this->getRealCoOpName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $requests = $this->getCoOpRequests($playerName);
            if($requests[0] === $victimRealName)
            {
              $playerFile = $this->getSource()->getPlayerFile($playerName);
              $playerFile->removeNested("Co-Op.Requests");
              $playerFile->save();
              return true;
            }else{
              return false;
            }
          }else{
            return false;
          }
        }
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function getRealTradeName($player, string $victimName)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("TradeRequests")))
      {
        $realName = "";
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("TradeRequests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("TradeRequests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $realName = $request;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $realName;
      }else{
        return "";
      }
    }else{
      return "";
    }
  }
  
  public function getRealCoOpName($player, string $victimName)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
      {
        $realName = "";
        if(is_array($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $realName = $request;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $realName;
      }else{
        return "";
      }
    }else{
      return "";
    }
  }
  
  public function getRealRequestName($player, string $victimName)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("FriendRequests")))
      {
        $realName = "";
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("FriendRequests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("FriendRequests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $realName = $request;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $realName;
      }else{
        return "";
      }
    }else{
      return "";
    }
  }
  
  public function getRealFriendName($player, string $victimName)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("Friends")))
      {
        $realName = "";
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("Friends")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("Friends") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $realName = $request;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $realName;
      }else{
        return "";
      }
    }else{
      return "";
    }
  }
  
  public function hasTradeRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("TradeRequests")))
      {
        $hasRequest = false;
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("TradeRequests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("TradeRequests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $hasRequest = true;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $hasRequest;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function hasCoOpRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
      {
        $hasRequest = false;
        if(is_array($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $hasRequest = true;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $hasRequest;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function hasFriendRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("FriendRequests")))
      {
        $hasRequest = false;
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("FriendRequests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("FriendRequests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $hasRequest = true;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $hasRequest;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function isCoOp($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $a_Island = $this->getSource()->getPlayerFile($playerName)->get("Island");
    $b_Island = $this->getSource()->getPlayerFile($victimName)->get("Island");
    if($a_Island !== $b_Island)
    {
      return false;
    }else{
      return true;
    }
  }
  
  public function isFriend($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("Friends")))
      {
        $isFriend = false;
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("Friends")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("Friends") as $friend)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($friend, $name) === 0)
            {
              $curDelta = strlen($friend) - strlen($name);
              if($curDelta < $delta)
              {
                $isFriend = true;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $isFriend;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function getFriends($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("Friends")))
      {
        $friends = [];
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("Friends")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("Friends") as $friend)
          {
            $friends[] = $friend;
          }
        }
        return $friends;
      }else{
        return [];
      }
    }else{
      return [];
    }
  }
  
  public function getTradeRequests($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("TradeRequests")))
      {
        $requests = [];
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("TradeRequests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("TradeRequests") as $request)
          {
            $requests[] = $request;
          }
        }
        return $requests;
      }else{
        return [];
      }
    }else{
      return [];
    }
  }
  
  public function getCoOpRequests($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
      {
        $requests = [];
        if(is_array($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests") as $request)
          {
            $requests[] = $request;
          }
        }
        return $requests;
      }else{
        return [];
      }
    }else{
      return [];
    }
  }
  
  public function getFriendRequests($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->get("FriendRequests")))
      {
        $requests = [];
        if(is_array($this->getSource()->getPlayerFile($playerName)->get("FriendRequests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->get("FriendRequests") as $request)
          {
            $requests[] = $request;
          }
        }
        return $requests;
      }else{
        return [];
      }
    }else{
      return [];
    }
  }
  
  public function getLoanMerit($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Merit");
      return $data;
    }else{
      return null;
    }
  }
  
  public function getLoan($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Loan");
      return $data;
    }else{
      return null;
    }
  }
  
  public function addLoan($player, int $amount): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $economy->addToPlayerBalance($playerName, $amount);
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Loan", $amount);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function reduceLoan($player, int $amount): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $economy = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
      $economy->subtractFromPlayerBalance($playerName, $amount);
      $loan = $this->getLoan($playerName);
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Loan", ($loan - $amount));
      $playerFile->save();
      if($loan - $amount === 0)
      {
        $playerFile->setNested("Bank.Time", 0);
        $playerFile->setNested("Bank.MaxTime", 0);
        $playerFile->save();
      }
      return true;
    }else{
      return false;
    }
  }
  
  public function addLoanTime($player, int $time): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $oldTime = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Time");
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.Time", $oldTime + $time);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function addKitTime($player, string $kit): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $oldTime = $this->getSource()->getPlayerFile($playerName)->getNested("Kits.$kit.Time");
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Kits.$kit.Time", $oldTime + 1);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function damageParticle(Position $Pos, float $Damage)
  {
    $pk = new AddPlayerPacket();
    $Id = Entity::nextRuntimeId();
    $pk->actorRuntimeId = $Id;
    $pk->actorUniqueId = $Id;
    $world = $Pos->getWorld();
    $pk->position = $Pos->add(0, 1, 0);
    $pk->uuid = Uuid::uuid4();
    $pk->gameMode = 1;
    $pk->adventureSettingsPacket = AdventureSettingsPacket::create(0, 0, 0, 0, 0, $Id);
    $pk->item = ItemStackWrapper::legacy(ItemStack::null());
    $pk->username = "§c❁ §e$Damage";
    $pk->metadata = [
      EntityMetadataProperties::FLAGS => new LongMetadataProperty(1 << EntityMetadataFlags::IMMOBILE),
      EntityMetadataProperties::SCALE => new FloatMetadataProperty(0.01)
      ];
    $packet = RemoveActorPacket::create($pk->actorRuntimeId);
    $world->broadcastPacketToViewers($Pos, $pk);
    $this->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
      function() use($Pos, $world, $packet): void
      {
        $world->broadcastPacketToViewers($Pos, $packet);
      }
    ), 15);
  }
  
  public function setMaxLoanTime($player, int $time): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Bank.MaxTime", $time);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function recoverLoan($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      if($this->getLoan($playerName) > 0)
      {
        $loan = $this->getLoan($playerName);
        $playerFile = $this->getSource()->getPlayerFile($playerName);
        $this->getSource()->getServer()->getWorldManager()->loadWorld($playerFile->get("Island"));
        $world = $this->getSource()->getServer()->getWorldManager()->getWorldByName($playerFile->get("Island"));
        $this->getSource()->getServer()->getWorldManager()->unloadWorld($world);
        $this->deleteDirectory(Server::getInstance()->getDataPath() . "worlds/" . $playerFile->get("Island"));
        $this->createIsland($playerName);
        $a_merit = $this->getSource()->getPlayerFile($playerName)->getNested("Bank.Merit");
        $b_merit = $loan/($a_merit * 1000);
        $playerFile->setNested("Bank.Merit", $b_merit);
        $playerFile->setNested("Bank.Loan", 0);
        $playerFile->save();
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function getPlayerPets($player): ?array
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("Pet.All");
      return $data;
    }else{
      return [];
    }
  }
  
  public function setPlayerCurrentPet($player, string $pet): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Pet.Current", $pet);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
    public function getPlayerCurrentPet($player): ?string
    {
      if($player instanceof Player)
      {
        $playerName = $player->getName();
      }else{
        $playerName = $player;
      }
      if($this->hasSkyIsland($playerName))
      {
        $data = $this->getSource()->getPlayerFile($playerName)->getNested("Pet.Current");
        return $data;
      }else{
        return null;
      }
  }
  
  public function spawnAssistant($world)
  {
    $worldName = $world->getFolderName();
    $location = new Location(230.5, 65.0, 277.5, $world, 0.0, 0.0);
    $entity = new Assistant($location);
    $entity->setNameTag("§eJerry\nClick To Use");
    $entity->setNameTagAlwaysVisible(true);
    $entity->spawnToAll();
  }
  
  public function spawnPet($player, string $pet): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $player = Server::getInstance()->getPlayerExact($playerName);
    if($player instanceof Player)
    {
      $petInfo = $this->getPetInfo($pet);
      if($petInfo !== [])
      {
        $Variables = VariablesAPI::getInstance();
        $KeyExists = $Variables->hasKey("Pets", $player->getName());
        if($KeyExists)
        {
          $Pet = $Variables->getVariable("Pets")[$player->getName()];
          if(!$Pet->isClosed())
          {
            $Pet->flagForDespawn();
          }
          $Variables->removeKey("Pets", $player->getName());
        }
        $skinPath = Server::getInstance()->getDataPath() . "plugin_data/SkyIsland/" . $petInfo["Skin"];
        $geometryPath = Server::getInstance()->getDataPath() . "plugin_data/SkyIsland/" . $petInfo["Geometry"];
        $bytes = $this->calculateSkinBytes($skinPath);
        $skin = new Skin($petInfo["Name"], $bytes, "", "geometry.Pet", file_get_contents($geometryPath));
        $nbt = CompoundTag::create()
         ->setTag("PetInfo", 
          CompoundTag::create()
          ->setString("Owner", $player->getName())
          ->setString("Name", $petInfo["Name"])
          ->setString("Tier", $petInfo["Tier"])
          ->setString("Geometry", $petInfo["Geometry"])
          ->setString("Skin", $petInfo["Skin"]))
         ->setTag("Skin",
          CompoundTag::create()
          ->setString("Name", $skin->getSkinId())
          ->setByteArray("Data", $skin->getSkinData())
          ->setString('GeometryData', $skin->getGeometryData()));
        $entity = new Pet($player->getLocation(), $skin, $nbt, $player);
        $Pet = VariablesAPI::getInstance()->getVariable("Pets");
        $Pet[$player->getName()] = $entity;
        VariablesAPI::getInstance()->setVariable("Pets", $Pet);
        $entity->setNameTagAlwaysVisible(true);
        $entity->setNameTag($petInfo["Name"]);
        $entity->spawnToAll();
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function FullArmorProperty(Player $player, string $armor, string $object)
  {
    if($object === "Add")
    {
      if($armor === "Miner")
      {
        $effectManager = $player->getEffects();
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("haste"), 0x7fffffff, 2, false));
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("speed"), 0x7fffffff, 2, false));
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("night_vision"), 0x7fffffff, 1, false));
      }elseif($armor === "Farmer")
      {
        $effectManager = $player->getEffects();
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("haste"), 0x7fffffff, 1, false));
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("speed"), 0x7fffffff, 1, false));
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("absorption"), 0x7fffffff, 2, false));
      }elseif($armor === "Lumberjack")
      {
        $effectManager = $player->getEffects();
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("haste"), 0x7fffffff, 2, false));
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("speed"), 0x7fffffff, 1, false));
        $effectManager->add(new EffectInstance(StringToEffectParser::getInstance()->parse("absorption"), 0x7fffffff, 2, false));
      }
    }elseif($object === "Remove")
    {
      $effectManager = $player->getEffects();
      $effectManager->clear();
    }
  }
  
  public function getObjective($player): ?string
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $objective = $this->getSource()->getPlayerFile($playerName)->get("Objective");
      return $objective;
    }else{
      return null;
    }
  }
  
  public function getNextObjective($player): ?string
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $array = array(
        "Break-Log" => "Craft-WorkBench",
        "Craft-WorkBench" => "Craft-Pickaxe",
        "Craft-Pickaxe" => "Go-In-Portal",
        "Go-In-Portal" => "Travel-On-Ship",
        "Travel-On-Ship" => "Farm-Wheat",
        "Farm-Wheat" => "Mine-Iron",
        "Mine-Iron" => "Make-Friend",
        "Make-Friend" => "Upgrade-Worker",
        "Upgrade-Worker" => "Deposit-Money",
        "Deposit-Money" => "Aquire-Diamond-Worker",
        "Aquire-Diamond-Worker" => "Unlock-PotionBag",
        "Unlock-PotionBag" => "Open-EnderChest",
        "Open-EnderChest" => "Unlock-Pet",
        "Unlock-Pet" => "-"
        );
      $nextObjective = $array[$this->getObjective($player)];
      return $nextObjective;
    }else{
      return null;
    }
  }
  
  public function setObjective($player, $objective)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("Objective", $objective);
      $playerFile->save();
    }
  }
  
  public function getCoOpRolePerm(string $role)
  {
    switch($role)
    {
      case "Co-Owner":
        $array = array(
          "Build",
          "Interact"
          );
        return $array;
      case "Senior-Member":
        $array = array(
          "Build",
          "Interact"
          );
        return $array;
        break;
      case "Member":
        $array = array(
          "Build",
          "Interact"
          );
        return $array;
        break;
      case "Builder":
        $array = array(
          "Build"
          );
        return $array;
        break;
      default:
        return array();
        break;
    }
  }
  
  public function hasCoOpPerm($player, string $perm)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $role = $this->getCoOpRole($playerName);
    $perms = $this->getCoOpRolePerm($role);
    if($role !== "Owner" && $role !== "Co-Owner")
    {
      if(in_array($perm, $perms))
      {
        return true;
      }else{
        return false;
      }
    }else{
      return true;
    }
  }
  
  public function getIngredients($items, $recipe)
  {
    $ingredients = array();
    if($recipe instanceof ShapedRecipe)
    {
      $Grid = new Grid();
      foreach($items as $key => $item)
      {
        $slot = (int) $key;
        $Grid->setItem($slot, $item);
      }
      $minX = PHP_INT_MAX;
      $minY = PHP_INT_MAX;
      $empty = true;
      for($y = 0; $y < 3; ++$y)
      {
        for($x = 0; $x < 3; ++$x)
        {
          if(!$Grid->isSlotEmpty($y * 3 + $x))
          {
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $empty = false;
          }
        }
      }
      if(!$empty)
      {
        $startX = $minX;
        $startY = $minY;
      }else{
        $startX = null;
        $startY = null;
      }
      for($y = 0; $y < $recipe->getHeight(); ++$y)
      {
        for($x = 0; $x < $recipe->getWidth(); ++$x)
        {
          $slot = ($y + $startY) * 3 + ($x + $startX);
          $given = $Grid->getItem($slot);
          if($given->getName() !== "§r §7 §r")
          {
            $required = $recipe->getIngredient($x, $y);
            $ingredients["$slot"] = [$required->getId(), $required->getMeta(), $required->getCount(), $required->getName()];
          }
        }
      }
    }elseif($recipe instanceof ShapelessRecipe)
    {
      $ingredients = $recipe->getIngredientList();
    }
    return $ingredients;
  }
  
  public function matchRecipe(array $items)
  {
    $CraftingManager = $this->getSource()->getServer()->getCraftingManager();
    $ShapedRecipes = $CraftingManager->getShapedRecipes();
    $ShapelessRecipes = $CraftingManager->getShapelessRecipes();
    $Grid = new Grid();
    foreach($items as $key => $item)
    {
      $slot = (int) $key;
      $Grid->setItem($slot, $item);
    }
    $IsShaped = false;
    foreach($ShapedRecipes as $ShapedRecipe)
    {
      foreach($ShapedRecipe as $Recipe)
      {
        if($Recipe->matchesCraftingGrid($Grid))
        {
          $IsShaped = true;
          return $Recipe;
        }
      }
    }
    if(!$IsShaped)
    {
      foreach($ShapelessRecipes as $ShapelessRecipe)
      {
        foreach($ShapelessRecipe as $Recipe)
        {
          if($Recipe->matchesCraftingGrid($Grid))
          {
            return $Recipe;
          }
        }
      }
    }
    return null;
  }
  
  public function matchRecipeByOutput(Item $output)
  {
    $CraftingManager = $this->getSource()->getServer()->getCraftingManager();
    $ShapedRecipes = $CraftingManager->getShapedRecipes();
    $ShapelessRecipes = $CraftingManager->getShapelessRecipes();

    $Recipes = [];
    foreach($ShapedRecipes as $ShapedRecipe)
    {
      foreach($ShapedRecipe as $Recipe)
      {
        if($Recipe->getResults()[0]->getId() === $output->getId() && $Recipe->getResults()[0]->getMeta() === $output->getMeta())
        {
          $Recipes[] = $Recipe;
        }
      }
    }
    foreach($ShapelessRecipes as $ShapelessRecipe)
    {
      foreach($ShapelessRecipe as $Recipe)
      {
        if($Recipe->getResults()[0]->getId() === $output->getId() && $Recipe->getResults()[0]->getMeta() === $output->getMeta())
        {
          $Recipes[] = $Recipe;
        }
      }
    }
    if($Recipes !== [])
    {
      $Key = array_rand($Recipes);
      $Recipe = $Recipes[$Key];
      return $Recipe;
    }else{
      return null;
    }
  }
  
  public function getCoOpRole($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $role = $this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Role");
      return $role;
    }else{
      return null;
    }
  }
  
  public function getMaxMembers($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $maxMembers = $this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.MaxMembers");
      return $maxMembers;
    }else{
      return 5;
    }
  }
  
  public function getMembers($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyIsland($playerName))
    {
      $members = $this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Members");
      return $members;
    }else{
      return 0;
    }
  }
  
  public function getPetInfo(string $petName)
  {
    $petInfo = array();
    foreach($this->getSource()->getConfigFile()->get("Pets") as $pet)
    {
      if(((string)$pet[0]) == ((string)$petName))
      {
        $petInfo = array(
          "Name" => $pet[0],
          "Tier" => $pet[1],
          "Geometry" => $pet[2],
          "Skin" => $pet[3],
        );
        break;
      }
    }
    return $petInfo;
  }
  
  public function getAllRecipes(): array
  {
    $array = [];
    foreach(scandir($this->getSource()->getDataFolder() . "recipes") as $key => $recipeFile)
    {
      if(is_file($this->getSource()->getDataFolder() . "recipes/$recipeFile"))
      {
        $array[] = $recipeFile;
      }
    }
    return $array;
  }
  
  public function removeKeyFromArray(array $a_array, $a_key): array
  {
    $b_array = [];
    foreach($a_array as $b_key)
    {
      if($a_key !== $b_key)
      {
        $b_array[] = $b_key;
      }
    }
    return $b_array;
  }
  
  public function removeItemFromDrops(array $a_array, Item $a_item): array
  {
    $b_array = [];
    foreach($a_array as $b_item)
    {
      if($b_item->getId() !== $a_item->getId() && $a_item->getMeta() !== $b_item->getMeta())
      {
        $b_array[] = $b_item;
      }
    }
    return $b_array;
  }
  
  public function addSound(array $players, string $trackName = "", float $volume = 1.0, float $pitch = 1.0): void{
		foreach($players as $player){
			$pk = new PlaySoundPacket();
			$pk->soundName = $trackName;
			$pk->x = (int)$player->getPosition()->x;
			$pk->y = (int)$player->getPosition()->y;
			$pk->z = (int)$player->getPosition()->z;
			$pk->volume = $volume;
			$pk->pitch = $pitch;

			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}
  
  public function changeNumericFormat(int $number, string $format)
  {
    if($format === "k")
    {
      $numeric = $number/1000;
      $data = $numeric."k";
      return $data;
    }
    if($format === "time")
    {
      $secs = (int)$number;
        if($secs === 0)
        {
          return '0 secs';
        }
        $mins  = 0;
        $hours = 0;
        $days  = 0;
        $weeks = 0;
        if($secs >= 60)
        {
          $mins = (int)($secs / 60);
          $secs = $secs % 60;
        }
        if($mins >= 60)
        {
          $hours = (int)($mins / 60);
          $mins = $mins % 60;
        }
        if($hours >= 24)
        {
          $days = (int)($hours / 24);
          $hours = $hours % 60;
        }
        if($days >= 7)
        {
          $weeks = (int)($days / 7);
          $days = $days % 7;
        }
        
        $result = '';
        if($weeks)
        {
            $result .= "$weeks weeks ";
        }
        if($days)
        {
            $result .= "$days days ";
        }
        if($hours)
        {
            $result .= "$hours hours ";
        }
        if($mins)
        {
            $result .= "$mins mins ";
        }
        if($secs)
        {
            $result .= "$secs secs ";
        }
        $result = rtrim($result);
        return $result;
    }
  }
  
  public function calculateSkinBytes(string $imagePath): string
  {
    $image = @imagecreatefrompng($imagePath);
    $bytes = '';
    $imageSize = (int) @getimagesize($imagePath)[1];
    for($y = 0; $y < $imageSize; $y++) {
      for($x = 0; $x < $imageSize; $x++) {
        $colorAt = @imagecolorat($image, $x, $y);
        $a = ((~((int)($colorAt >> 24))) << 1) & 0xff;
        $r = ($colorAt >> 16) & 0xff;
        $g = ($colorAt >> 8) & 0xff;
        $b = $colorAt & 0xff;
        $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
      }
    }
    @imagedestroy($image);
    return $bytes;
    }
  
  public function getItemType(Item $item)
  {
    $id = $item->getId();
    $array_tool = array(269, 270, 271, 273, 274, 275, 277, 278, 279, 284, 285, 286, 256, 257, 258, 290, 291, 292, 293, 294);
    $array_weapon = array(268, 271, 272, 275, 276, 279, 283, 286, 267, 258, 261);
    $array_armor = array(298, 299, 300, 301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317);
    $type = array
    (
      268 => "Sword",
      271 => "Axe",
      272 => "Sword",
      275 => "Axe",
      276 => "Sword",
      279 => "Axe",
      283 => "Sword",
      286 => "Axe",
      267 => "Sword",
      258 => "Axe",
      261 => "Bow",
      298 => "Helmet",
      299 => "Chestplate",
      300 => "Leggings",
      301 => "Boots",
      302 => "Helmet",
      303 => "Chestplate",
      304 => "Leggings",
      305 => "Boots",
      306 => "Helmet",
      307 => "Chestplate",
      308 => "Leggings",
      309 => "Boots",
      310 => "Helmet",
      311 => "Chestplate",
      312 => "Leggings",
      313 => "Boots"
    );
    if(in_array($id, $array_tool, true))
    {
      return array("Tool");
    }elseif(in_array($id, $array_weapon, true))
    {
      return array("Weapon", $type[$id]);
    }elseif(in_array($id, $array_armor, true))
    {
      return array("Armor", $type[$id]);
    }else{
      return array();
    }
  }
  
  public function setScoreboardEntry(Player $player, int $score, string $msg, string $objName)
  {
    $entry = new ScorePacketEntry();
    $entry->objectiveName = $objName;
    $entry->type = 3;
    $entry->customName = "$msg";
    $entry->score = $score;
    $entry->scoreboardId = $score;
    $playerk = new SetScorePacket();
    $playerk->type = 0;
    $playerk->entries[$score] = $entry;
    $player->getNetworkSession()->sendDataPacket($playerk);
  }

  public function createScoreboard(Player $player, string $title, string $objName, string $slot = "sidebar", $order = 0)
  {
    $playerk = new SetDisplayObjectivePacket();
    $playerk->displaySlot = $slot;
    $playerk->objectiveName = $objName;
    $playerk->displayName = $title;
    $playerk->criteriaName = "dummy";
    $playerk->sortOrder = $order;
    $player->getNetworkSession()->sendDataPacket($playerk);
  }

  public function removeScoreboard(Player $player, string $objName)
  {
    $playerk = new RemoveObjectivePacket();
    $playerk->objectiveName = $objName;
    $player->getNetworkSession()->sendDataPacket($playerk);
  }
  
  public function matchItem(Item $item, $inv)
  {
    $contents = $inv->getContents();
    $slot = array_search($item, $contents);
    if($slot === false){
      return null;
    }else{
      return array($slot, $item);
    }
  }
  
  public function hasItem(Item $a_item, $inv, bool $nameCheck = false)
  {
    $count = 0;
    $hasItem = false;
    foreach(array_reverse($inv->getContents(), true) as $slot => $b_item)
    {
      if($b_item->getId() === $a_item->getId() && $b_item->getMeta() === $a_item->getMeta())
      {
        if(!$nameCheck || $a_item->getName() === $b_item->getName())
        {
          if(!$hasItem)
          {
            $hasItem = true;
          }
          $count += $b_item->getCount();
        }
      }
    }
    if($count >= $a_item->getCount())
    {
      return $hasItem;
    }else{
      return false;
    }
  }
  
  public function removeItem($inv, bool $checkName = false, Item ...$slots)
  {
		$itemSlots = [];
		foreach($slots as $slot){
			if(!$slot->isNull()){
				$itemSlots[] = clone $slot;
			}
		}

		for($i = 0, $size = $inv->getSize(); $i < $size; ++$i){
			$item = $inv->getItem($i);
			if($item->isNull()){
				continue;
			}
			foreach($itemSlots as $index => $slot){
				if($slot->equals($item, !$slot->hasAnyDamageValue(), false)){
				  if(!$checkName || $item->getName() === $slot->getName()){
            $amount = min($item->getCount(), $slot->getCount());
            $slot->setCount($slot->getCount() - $amount);
            $item->setCount($item->getCount() - $amount);
            $inv->setItem($i, $item);
            if($slot->getCount() <= 0){
              unset($itemSlots[$index]);
            }
				  }
				}
			}
			if(count($itemSlots) === 0){
				break;
			}
		}
  }
  
  public function addUnbreakable($pos, $world)
  {
    $x = (int) $pos->getX();
    $y = (int) $pos->getY();
    $z = (int) $pos->getZ();
    $a_world = (string) $world->getFolderName();
    if(class_exists($this->getSource()->getDataFolder() . "unbreakables"))
    {
      mkdir($this->getSource()->getDataFolder() . "unbreakables");
    }
    $name = $this->randomString(50, $this->getSource()->getDataFolder() . "unbreakables", "yml");
    $this->getSource()->saveResource("unbreakables/$name");
    $file = new Config($this->getSource()->getDataFolder() . "unbreakables/$name", Config::YAML, [
      ]);
    $file->setNested("Unbreakable", [$x, $y, $z, $a_world]);
    $file->save();
  }
  
  public function removeUnbreakable($pos, string $worldName)
  {
    $x = (int) $pos->getX();
    $y = (int) $pos->getY();
    $z = (int) $pos->getZ();
    foreach($this->getUnbreakables() as $unbreakable)
    {
      if(is_file($this->getSource()->getDataFolder() . "unbreakables/$unbreakable"))
      {
        $file = new Config($this->getSource()->getDataFolder() . "unbreakables/$unbreakable", Config::YAML, [
          ]);
        $key = $file->get("Unbreakable");
        $a_Unbreakable = "{$key[0]}:{$key[1]}:{$key[2]}:{$key[3]}";
        $b_Unbreakable = "{$x}:{$y}:{$z}:{$worldName}";
        if($a_Unbreakable === $b_Unbreakable)
        {
          unlink($this->getSource()->getDataFolder() . "unbreakables/$unbreakable");
          break;
        }
      }
    }
  }
  
  public function IsUnbreakable($x, $y, $z, $worldName): bool
  {
    $found = false;
    foreach($this->getUnbreakables() as $unbreakable)
    {
      if(is_file($this->getSource()->getDataFolder() . "unbreakables/$unbreakable"))
      {
        $file = new Config($this->getSource()->getDataFolder() . "unbreakables/$unbreakable", Config::YAML, [
          ]);
        $key = $file->get("Unbreakable");
        $a_Unbreakable = "{$key[0]}:{$key[1]}:{$key[2]}:{$key[3]}";
        $b_Unbreakable = "{$x}:{$y}:{$z}:{$worldName}";
        if($a_Unbreakable === $b_Unbreakable)
        {
          $found = true;
          break;
        }
      }
    }
    return $found;
  }
  
  public function getUnbreakables(): array
  {
    $array = [];
    foreach(scandir($this->getSource()->getDataFolder() . "unbreakables") as $key => $file)
    {
      $array[] = $file;
    }
    return $array;
  }
  
  public function isConnected(array $blocks, Block $a_block, $world, int $tries)
  {
    return true;
  }
  
  public function deleteDirectory($dirPath)
  {
    if(is_dir($dirPath))
    {
      $objects = scandir($dirPath);
      foreach($objects as $object)
      {
        if($object != "." && $object !="..")
        {
          if(filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir")
          {
            $this->deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
          }else{
            unlink($dirPath . DIRECTORY_SEPARATOR . $object);
          }
        }
      }
    reset($objects);
    rmdir($dirPath);
    }
  }
  
  public function getWorkers($world)
  {
    $array = array();
    $entities = $world->getEntities();
    foreach($entities as $entity)
    {
      if($entity instanceof Worker)
      {
        $array[] = $entity;
      }
    }
    return $array;
  }
  
  public function randomString(int $length = 40, $path = "", string $extension = "yml")
  {
    $key = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));
    for ($i = 0; $i < $length; $i++)
    {
        $key .= $keys[array_rand($keys)];
    }
    if(is_array($path))
    {
      if(!array_key_exists($key, $path))
      {
        return $key;
      }else{
        return $this->randomString($length, $path, $extension);
      }
    }elseif($path === "" || !file_exists($path . "/$key" . "." . $extension))
    {
      return $key;
    }else{
      return $this->randomString($length, $path, $extension);
    }
  }
  
  public static function getSource(): SkyIsland
  {
    $SkyIsland = SkyIsland::getInstance();
    return $SkyIsland;
  }
  
  public function hasSkyIsland($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(file_exists($this->getSource()->getDataFolder() . "players/$playerName" . ".yml"))
    {
      return true;
    }else{
      return false;
    }
  }
  
}