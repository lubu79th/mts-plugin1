<?php

namespace amiexd\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use amiexd\plugin;

class SimpleMessagesTask extends PluginTask{

    public function __construct(plugin $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick){
        $this->getOwner();
        $this->plugin->config = $this->owner->getConfig()->getAll();
        $messages = $this->plugin->config["messages"];
        $messagekey = array_rand($messages, 1);
        $message = $messages[$messagekey];
        Server::getInstance()->broadcastMessage($this->plugin->config["color"]."[".$this->plugin->config["prefix"]."]: ".$message);
    }

}
