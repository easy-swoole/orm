<?php


namespace EasySwoole\ORM;


use EasySwoole\ORM\Driver\DriverInterface;

class DbManager
{
    private static $instance;

    private $con = [];

    public static function getInstance():DbManager
    {
        if(!isset(self::$instance)){
            self::$instance = new static();
        }
        return self::$instance;
    }

    function addConnection(DriverInterface $driver,string $name = 'default')
    {
        $this->con[$name] = $driver;
        return $this;
    }

    function getConnection(string $name = 'default'):?DriverInterface
    {
        if(isset($this->con[$name])){
            return $this->con[$name];
        }else{
            return null;
        }
    }
}