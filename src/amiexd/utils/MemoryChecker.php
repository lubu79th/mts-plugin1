<?php

namespace amiexd\utils;

use amiexd\plugin;

class MemoryChecker{
    
    public static function calculateBytes($toCheck){
        $byteLimit = (int) substr(trim($toCheck), 0, 1);
        switch(strtoupper(substr($toCheck, -1))){
            case "T": //terabyte
                $byteLimit *= 1024;
            case "G": //gigabyte
                $byteLimit *= 1024;
            case "M": //megabyte
                $byteLimit *= 1024;
            case "K": //kilobyte
                $byteLimit *= 1024;
            case "B": //byte
                $byteLimit *= 1024;
                break;
        }
        return $byteLimit;
    }
   
    public static function isOverloaded($toCheck){
        return memory_get_usage(true) > self::calculateBytes($toCheck);
    }
}
