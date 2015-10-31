<?php

namespace amiexd\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use amiexd\plugin;

class SimpleMessagesTask extends PluginTask{

    private $plugin;

    public function __construct(plugin $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function getPlugin(){
        return $this->plugin;
    }

    public function onRun($currentTick){
        $this->getOwner();
        $this->plugin->messagestask = $this->owner->getConfig()->getAll();
        $messages = $this->plugin->messagestask["messagetask"];
        $messagekey = array_rand($messages, 1);
        $message = $messages[$messagekey];
        Server::getInstance()->broadcastMessage($this->plugin->messagestask["color"]."[".$this->plugin->messagestask["prefix"]."]: ".$message);
    }

}
