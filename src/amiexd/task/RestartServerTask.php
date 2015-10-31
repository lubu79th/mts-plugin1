<?php

namespace amiexd\task;

use pocketmine\scheduler\PluginTask;
use amiexd\plugin;

class RestartServerTask extends PluginTask{

    private $plugin;
    
    public function __construct(plugin $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function getPlugin(){
        return $this->plugin;
    }
    
    public function onRun($currentTick){
        if(!$this->getPlugin()->isTimerPaused()){
            $this->getPlugin()->subtractTime(1);
            if($this->getPlugin()->getTime() <= $this->getPlugin()->getConfig()->getNested("restart.startCountdown")){
                $this->getPlugin()->broadcastTime($this->getPlugin()->getConfig()->getNested("restart.displayType"));
            }
            if($this->getPlugin()->getTime() < 1){
                $this->getPlugin()->initiateRestart(RestartMe::TYPE_NORMAL);
            }
        }
    }
}
