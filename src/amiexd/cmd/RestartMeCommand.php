<?php

namespace amiexd\cmd;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use amiexd\utils\MemoryChecker;
use amiexd\plugin;

class RestartMeCommand extends Command{
    /** @var RestartMe */
    private $plugin;
    /**
     * @param RestartMe $plugin
     */
    public function __construct(plugin $plugin){
        parent::__construct("restartme");
        $this->setDescription("Shows all RestartMe commands");
        $this->setUsage("/restartme <sub-command> [parameters]");
        $this->setPermission("restartme.command.restartme");
        $this->setAliases(["rm"]);
        $this->plugin = $plugin;
    }
    /** 
     * @return RestartMe 
     */
    public function getPlugin(){
        return $this->plugin;
    }
    /** 
     * @param CommandSender $sender 
     */
    private function sendCommandHelp(CommandSender $sender){
        $sender->sendMessage("RestartMe commands:");
        $sender->sendMessage("/restartme add: Adds n seconds to the timer");
        $sender->sendMessage("/restartme help: Shows all RestartMe commands");
        $sender->sendMessage("/restartme memory: Shows memory usage information");
        $sender->sendMessage("/restartme set: Sets the timer to n seconds");
        $sender->sendMessage("/restartme start: Starts the timer");
        $sender->sendMessage("/restartme stop: Stops the timer");
        $sender->sendMessage("/restartme subtract: Subtracts n seconds from the timer");
        $sender->sendMessage("/restartme time: Gets the remaining time until the server restarts");
    }
    /**
     * @param CommandSender $sender
     * @param string $label
     * @param string[] $args
     */
    public function execute(CommandSender $sender, $label, array $args){
        if(isset($args[0])){
            switch(strtolower($args[0])){
                case "a":
                case "add":
                    if(isset($args[1])){
                        if(is_numeric($args[1])){
                            $time = (int) $args[1];
                            $this->getPlugin()->addTime($time);
                            $sender->sendMessage(TextFormat::GREEN."Added ".$time." to restart timer.");
                        }
                        else{
                            $sender->sendMessage(TextFormat::RED."Time value must be numeric.");
                        } 
                    }
                    else{
                        $sender->sendMessage(TextFormat::RED."Please specify a time value.");
                    }
                    break;
                case "?":
                case "help":
                    $this->sendCommandHelp($sender);
                    break;
                case "m":
                case "memory":
                    $memLimit = $this->getPlugin()->getMemoryLimit();
                    $sender->sendMessage("Bytes: ".memory_get_usage(true)."/".MemoryChecker::calculateBytes($memLimit));
                    $sender->sendMessage("Memory-limit: ".$memLimit);
                    $sender->sendMessage("Overloaded: ".(MemoryChecker::isOverloaded($memLimit) ? TextFormat::GREEN."yes" : TextFormat::RED."no"));
                    break;
                case "set":
                    if(isset($args[1])){
                        if(is_numeric($args[1])){
                            $time = (int) $args[1];
                            $this->getPlugin()->setTime($time);
                            $sender->sendMessage(TextFormat::GREEN."Set restart timer to ".$time.".");
                        }
                        else{
                            $sender->sendMessage(TextFormat::RED."Time value must be numeric.");
                        } 
                    }
                    else{
                        $sender->sendMessage(TextFormat::RED."Please specify a time value.");
                    }
                    break;
                case "start":
                    if($this->getPlugin()->isTimerPaused()){
                        $this->getPlugin()->setPaused(false);
                        $sender->sendMessage(TextFormat::YELLOW."Timer is no longer paused.");
                    }
                    else{
                        $sender->sendMessage(TextFormat::RED."Timer is not paused.");
                    }
                    break;
                case "stop":
                    if($this->getPlugin()->isTimerPaused()){
                        $sender->sendMessage(TextFormat::RED."Timer is already paused.");
                    }
                    else{
                        $this->getPlugin()->setPaused(true);
                        $sender->sendMessage(TextFormat::YELLOW."Timer has been paused.");
                    }
                    break;
                case "s":
                case "subtract":
                    if(isset($args[1])){
                        if(is_numeric($args[1])){
                            $time = (int) $args[1];
                            $this->getPlugin()->subtractTime($time);
                            $sender->sendMessage(TextFormat::GREEN."Subtracted ".$time." from restart timer.");
                        }
                        else{
                            $sender->sendMessage(TextFormat::RED."Time value must be numeric.");
                        } 
                    }
                    else{
                        $sender->sendMessage(TextFormat::RED."Please specify a time value.");
                    }
                    break;
                case "time":
                    $sender->sendMessage(TextFormat::YELLOW."Time remaining: ".$this->getPlugin()->getFormattedTime());
                    break;
                default:
                    $sender->sendMessage("Usage: ".$this->getUsage());
                    break;
            }
        }
        else{
            $this->sendCommandHelp($sender);
        }
    }
}
