<?php
namespace amiexd;

use pocketmine\Player;
use pocketmine\Serve;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecuter;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\item\Item;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\entity\DroppedItem;
use pocketmine\entity\Human;
use pocketmine\entity\Creature;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
//use amiexd\task\TimeCommand;
use amiexd\task\SimpleMessagesTask;
use amiexd\cmd\ClearLaggCommand;
use amiexd\item\Boat as BoatItem;
use amiexd\packet\PlayerInputPacket;
use amiexd\entity\Boat;
use amiexd\cmd\RestartMeCommand;
use amiexd\task\AutoBroadcastTask;
use amiexd\task\CheckMemoryTask;
use amiexd\task\RestartServerTask;

class plugin extends PluginBase implements Listener{
	 const TYPE_NORMAL = 0;
	 const TYPE_OVERLOADED = 1;
	 public $timer = 0;
	 public $paused = false;
    protected $exemptedEntities = [];
	 public $drops = array();
	 public $messagestask;
	 
	 public function onEnable(){
	Item::$list[333] = BoatItem::class;
    Item::addCreativeItem(new Item(333));
    $this->getServer()->addRecipe((new BigShapelessRecipe(Item::get(333, 0, 1)))->addIngredient(Item::get(Item::WOODEN_PLANK, null, 5))->addIngredient(Item::get(Item::WOODEN_SHOVEL, null, 1))); Entity::registerEntity("\\amiexd\\entity\\Boat", true);
    $this->getServer()->getNetwork()->registerPacket(0xae, PlayerInputPacket::class);
		 $this->saveFiles();
      $this->registerAll();
		 $this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	 private function saveFiles(){
		 if(file_exists($this->getDataFolder()."config.yml")){
			 if($this->getConfig()->get("version") !== $this->getDescription()->getVersion() or !$this->getConfig()->exists("version")){
				 $this->getServer()->getLogger()->warning("An invalid configuration file for ".$this->getDescription()->getName()." was detected.");
				 if($this->getConfig()->getNested("plugin.autoUpdate") === true){
					 $this->saveResource("config.yml", true);
					 $this->getServer()->getLogger()->warning("Successfully updated the configuration file for ".$this->getDescription()->getName()." to v".$this->getDescription()->getVersion().".");
					}
				}
			}else{
				 $this->saveDefaultConfig();
				 $this->getServer()->getLogger()->warning("Remember to use a server restarter script, or else this plugin won't work properly.");
			}
			
	}
	 private function registerAll(){
		 $this->setTime($this->getConfig()->getNested("restart.restartInterval") * 60);
		/***commandmap***/
		 $this->getServer()->getCommandMap()->register("mts", new ClearLaggCommand($this));
		 $this->getServer()->getCommandMap()->register("restartme", new RestartMeCommand($this));
		/***tasks***/
		 $this->getServer()->getScheduler()->scheduleRepeatingTask(new AutoBroadcastTask($this), ($this->getConfig()->getNested("restart.broadcastInterval") * 20));
		 if($this->getConfig()->getNested("restart.restartOnOverload") === true){
			 $this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckMemoryTask($this), 6000);
			 $this->getServer()->getLogger()->notice("Memory overload restarts are enabled. If memory usage goes above ".$this->getMemoryLimit().", the server will restart.");
			}else{
				 $this->getServer()->getLogger()->notice("Memory overload restarts are disabled.");
			}
		 $this->getServer()->getScheduler()->scheduleRepeatingTask(new SimpleMessagesTask($this), 500);
		 $this->getServer()->getScheduler()->scheduleRepeatingTask(new RestartServerTask($this), 20);
		 //$messagetime = intval($this->messagestask["messagetime"]) * 20;

	}
	 public function onDisable(){
	}
	
	 public function getTime(){
		 return $this->timer;
	}
	 public function setTime($seconds){
		 $this->timer = (int) $seconds;
	}
	 public function addTime($seconds){
		 if(is_numeric($seconds)) $this->timer += (int) $seconds;
	}
	 public function subtractTime($seconds){
		 if(is_numeric($seconds)) $this->timer -= (int) $seconds;
	}
	 public function isTimerPaused(){
		 return $this->paused === true;
	}
	 public function setPaused($value = true){
		 $this->paused = (bool) $value;
	}
	 public function getMemoryLimit(){
		 return strtoupper($this->getConfig()->getNested("restart.memoryLimit"));
	}
	 public function getFormattedTime(){
		 $hour = floor($this->getTime() / 3600);
		 $minute = floor(($this->getTime() / 60) - ($hour * 60));
		 $second = floor($this->getTime() % 60);
		 return $hour." hr ".$minute." min ".$second." sec";
	}
	 public function PlayerDeath(PlayerDeathEvent $event){
		 $player = $event->getEntity();
		  $this->drops[$player->getName()][1] = $player->getInventory()->getArmorContents();
		  $this->drops[$player->getName()][0] = $player->getInventory()->getContents();
		  $event->setDrops(array());
		  $player->teleport($player->getLevel()->getSpawn());
	}
	 public function PlayerRespawn(PlayerRespawnEvent $event){
		 $player = $event->getPlayer();
		 if (isset($this->drops[$player->getName()])) {
			 $player->getInventory()->setContents($this->drops[$player->getName()][0]);
			 $player->getInventory()->setArmorContents($this->drops[$player->getName()][1]);
			 unset($this->drops[$player->getName()]);
			}
	}
	/*
	 * ลบไอเท็ม $item
	*/
	 public function removeEntities(){
		 $i = 0;
		 foreach($this->getServer()->getLevels() as $level){
			 foreach($level->getEntities() as $entity){
				 if(!$this->isEntityExempted($entity) && !($entity instanceof Creature)){
					 $entity->close();
             $i++;
					}
			}
		}
		 return $i;
	}
	/*
	* ลบมอน $entity
	*/
	 public function removeMobs(){
		 $i = 0;
		 foreach($this->getServer()->getLevels() as $level){
			 foreach($level->getEntities() as $entity){
				 if(!$this->isEntityExempted($entity) && $entity instanceof Creature && !($entity instanceof Human)){
					 $entity->close();
					 $i++;
					}
			}
		}
		 return $i;
	}
	 public function getEntityCount(){
		 $ret = [0, 0, 0];
		 foreach($this->getServer()->getLevels() as $level){
			 foreach($level->getEntities() as $entity){
				 if($entity instanceof Human){
					 $ret[0]++;
					} else if($entity instanceof Creature){
					 $ret[1]++;
					} else {
					 $ret[2]++;
					}
			}
		}
		 return $ret;
	}
	 public function exemptEntity(Entity $entity){
		 $this->exemptedEntities[$entity->getID()] = $entity;
	}
	 public function isEntityExempted(Entity $entity){
		 return isset($this->exemptedEntities[$entity->getID()]);
	}
    public function onQuit(PlayerQuitEvent $event){
    if(isset($this->riding[$event->getPlayer()->getName()])){
      unset($this->riding[$event->getPlayer()->getName()]);
    }
  }

  public function broadcastTime($messageType){
        $message = str_replace("{RESTART_TIME}", $this->getTime(), $this->getConfig()->getNested("restart.countdownMessage"));
        switch(strtolower($messageType)){
            case "chat":
                $this->getServer()->broadcastMessage($message);
                break;
            case "popup":
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    $player->sendPopup($message);
                }
                break;
            case "tip":
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    $player->sendTip($message);
                }
                break;
        }
    }
   
    public function initiateRestart($mode){
        switch($mode){
            case self::TYPE_NORMAL:
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    $player->close("", $this->getConfig()->getNested("restart.quitMessage"));
                }
                $this->getServer()->getLogger()->info($this->getConfig()->getNested("restart.quitMessage"));
                break;
            case self::TYPE_OVERLOADED:
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    $player->close("", $this->getConfig()->getNested("restart.overloadQuitMessage"));
                }
                $this->getServer()->getLogger()->info($this->getConfig()->getNested("restart.overloadQuitMessage"));
                break;
        }
        $this->getServer()->shutdown();
    }
  public function onPacketReceived(DataPacketReceiveEvent $event){
    $packet = $event->getPacket();
    $player = $event->getPlayer();
    if($packet instanceof InteractPacket){
      $boat = $player->getLevel()->getEntity($packet->target);
      if($boat instanceof Boat){
        if($packet->action === 1){
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = $player->getId();
          $pk->type = 2;

          $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = 0;
          $pk->type = 2;
          $player->dataPacket($pk);

          $this->riding[$player->getName()] = $packet->target;
        }elseif($packet->action === 3){
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = $player->getId();
          $pk->type = 3;

          $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = 0;
          $pk->type = 3;
          $player->dataPacket($pk);

          if(isset($this->riding[$event->getPlayer()->getName()])){
            unset($this->riding[$event->getPlayer()->getName()]);
          }
        }
      }
    }elseif($packet instanceof MovePlayerPacket){
      if(isset($this->riding[$player->getName()])){
        $boat = $player->getLevel()->getEntity($this->riding[$player->getName()]);
        if($boat instanceof Boat){
          $boat->x = $packet->x;
          $boat->y = $packet->y;
          $boat->z = $packet->z;
        }
      }
    }
  }
}

