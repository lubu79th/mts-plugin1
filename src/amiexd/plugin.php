<?php
namespace amiexd;

use pocketmine\Player;
use pocketmine\Server;
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
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\entity\DroppedItem;
use pocketmine\entity\Human;
use pocketmine\entity\Creature;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\scheduler\CallbackTask;
//use amiexd\task\TimeCommand;
//use amiexd\task\SimpleMessagesTask;
use amiexd\cmd\ClearLaggCommand;
use amiexd\item\Boat as BoatItem;
use amiexd\packet\PlayerInputPacket;
use amiexd\entity\Boat;
//use amiexd\cmd\RestartMeCommand;
//use amiexd\task\AutoBroadcastTask;
//use amiexd\task\CheckMemoryTask;
//use amiexd\task\RestartServerTask;

class plugin extends PluginBase implements Listener{
    protected $exemptedEntities = [];
	 public $drops = array();
	 private $function_a1, $timer, $target, $EconomyS, $Kill, $killrate;
    private $webEndings = array(".net",".com",".leet.cc",".ddns.net","op",".tk"); 
	 
	 public function onEnable(){
	Item::$list[333] = BoatItem::class;
    Item::addCreativeItem(new Item(333));
    $this->getServer()->addRecipe((new BigShapelessRecipe(Item::get(333, 0, 1)))->addIngredient(Item::get(Item::WOODEN_PLANK, null, 5))->addIngredient(Item::get(Item::WOODEN_SHOVEL, null, 1))); Entity::registerEntity("\\amiexd\\entity\\Boat", true);
    $this->getServer()->getNetwork()->registerPacket(0xae, PlayerInputPacket::class);
		 //$this->saveFiles();
		 $this->reloadConfig();
		 $this->dropitemworld = $this->getConfig()->get("dropitemworld"); 
		 $this->saveDefaultConfig();
      $this->registerAll();
		 $this->getServer()->getPluginManager()->registerEvents($this, $this);
		 $this->EconomyS = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		 $this->killrate = $this->getServer()->getPluginManager()->getPlugin("KillRate");
		 $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask(array($this,"gui1")),10);
		 $this->timer = 0;
	}
	
		
	 private function registerAll(){
		/***commandmap***/
		 $this->getServer()->getCommandMap()->register("mts", new ClearLaggCommand($this));
		/***tasks***/
	}
	 public function gui1(){
		 foreach($this->getServer()->getOnlinePlayers() as $p){
                $tps = $this->getServer()->getTicksPerSecond();
			 $pName = $p->getPlayer()->getName();
			 $pMoney = $this->EconomyS->mymoney($pName);
			 $pOnline = count(Server::getInstance()->getOnlinePlayers());
			 $pFull = Server::getInstance()->getMaxPlayers();
			 $score = $this->killrate->getScore($pName);
			 /*$p->sendTip("                                            §bMts§a-§cSurvival§f: TPS[$tps]\n
§eyou§f: $pName\n                                                  §emoney§f: $pMoney\n                                                 §eonline§f: $pOnline\n                                                   §ekills§f: $score\n                                                                                                     §a-------------------"  ) ;*/
                $p->sendTip("                                                §bMts§a-§cSurvival§f:§aTPS§f[$tps] \n                                                        §eyou: $pName\n                                                         §eplayers: $pOnline\n                                                         §ekills: $score\n                                                         §emoney: $pMoney\n                                                 §a------------------------"  ) ;
		}
	}
	 public function onDisable(){
	}
	
	/*** Event ***/
	
	 public function onDrop(PlayerDropItemEvent $event){
		 $player = $event->getPlayer();
		 if(!$player->isOp()){
			 if(in_array($player->getLevel()->getName(), $this->dropitemworld)){ 
				 $player->sendMessage("§c[Error]§f คุณไม่สามารถทิ้งไอเท็มบนโลกนี้ได้");
			   $event->setCancelled();
			}
		}
	}
	 public function onPlayerLogin(PlayerLoginEvent $event){
		 $player = $event->getPlayer();
		 $x = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getX();
		 $y = $this->getServer()->getDefaultLevel()->getSafeSpawn()-> getY();
		 $z = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getZ();
		 $level = $this->getServer()->getDefaultLevel();
		 $player->setLevel($level);
		 $player->teleport(new Vector3($x, $y, $z, $level));
	}

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

 public function onChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $playername = $event->getPlayer()->getDisplayName();
        $parts = explode('.', $message);
        if(sizeof($parts) >= 4){
            if (preg_match('/[0-9]+/', $parts[1])){
                $event->setCancelled(true);
                $player->kick("§dระวังโดนแบน!");
                echo "[Advertising]: Kicked " . $playername . " For saying: ". $message . " \n";
            }
        }
        foreach ($this->webEndings as $url){
            if (strpos($message, $url) !== FALSE){
                $event->setCancelled(true);
                $player->kick("§dระวังโดนแบน!");
                echo "[Advertising]: Kicked " . $playername . " For saying: ". $message . " \n";
            }
        }
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

