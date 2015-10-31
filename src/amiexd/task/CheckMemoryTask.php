<?php

namespace amiexd\task;

use pocketmine\scheduler\PluginTask;
use restartme\utils\MemoryChecker;
use amiexd\plugin;

class CheckMemoryTask extends PluginTask{
    
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
            if(MemoryChecker::isOverloaded($this->getPlugin()->getMemoryLimit())){
                $this->getPlugin()->initiateRestart(RestartMe::TYPE_OVERLOADED);
            }
        }
    }
}
