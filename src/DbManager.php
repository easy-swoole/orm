<?php


namespace EasySwoole\TpORM;


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