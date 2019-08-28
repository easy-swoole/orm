<?php


namespace EasySwoole\ORM;


use EasySwoole\ORM\Driver\DriverInterface;

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

    function addConnection(string $name = 'default')
    {

    }

    function getConnection(string $name = 'default'):DriverInterface
    {

    }
}