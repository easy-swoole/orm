<?php


namespace EasySwoole\ORM;


class DbManager
{
    private static $instance;

    public static function getInstance():DbManager
    {
        if(!isset(self::$instance)){
            self::$instance = new static();
        }
        return self::$instance;
    }
}