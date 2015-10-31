<?php

namespace amiexd\cmd;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand; 
use amiexd\plugin;

class ClearLaggCommand extends Command implements PluginIdentifiableCommand {

  public $plugin;

  public function __construct(plugin $plugin) {
    parent::__construct("mts", "help server mts!", "/mts <clearlag>", ["lag"]);
    $this->setPermission("mts.command.clearlag");
    $this->plugin = $plugin;
  }

  public function getPlugin() {
    return $this->plugin;
  }

  public function execute(CommandSender $sender, $alias, array $args) {
    if(!$this->testPermission($sender)) {
      return false;
    }
    if(isset($args[0])) {
      switch($args[0]) {
        case "clear":
          $sender->sendMessage("Removed " . $this->getPlugin()->removeEntities() . " entities.");
          return true;
        case "check":
        case "count":
          $c = $this->getPlugin()->getEntityCount();
          $sender->sendMessage("There are " . $c[0] . " players, " . $c[1] . " mobs, and " . $c[2] . " entities.");
          return true;
        case "reload":
          // TODO
          return true;
        case "killmobs":
          $sender->sendMessage("Removed " . $this->getPlugin()->removeMobs() . " mobs.");
          return true;
        case "clearlag":
          $sender->sendMessage("Removed " . ($d = $this->getPlugin()->removeMobs()) . " mob" . ($d == 1 ? "" : "s") . " and " . ($d = $this->getPlugin()->removeEntities()) . " entit" . ($d == 1 ? "y" : "ies") . ".");
          return true;
        case "area":
          // TODO
          return true;
        case "unloadchunks":
          // TODO
          return true;
        case "chunk":
          // TODO
          return true;
        case "tpchunk":
          // TODO
          return true;
        default:
          return false;
      }
    }
    return false;
  }

}
