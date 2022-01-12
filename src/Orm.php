<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Config;
use EasySwoole\Pool\MagicPool;
use phpDocumentor\Reflection\Types\This;
use Swoole\Coroutine\MySQL;

class Orm
{
    use Singleton;

    /** @var callable|null */
    private $onQuery;

    protected $config = [];
    protected $pool = [];

    function addConnection(ConnectionConfig $config):Orm
    {
        $this->config[$config->getName()] = $config;
        return $this;
    }

    function setOnQuery(?callable $func = null):?callable
    {
        if($func){
            $this->onQuery = $func;
        }
        return $this->onQuery;
    }

    function fastQuery(?string $connectionName = null)
    {

    }



    function invoke(?string $connectionName = null):MySQL
    {

    }

    function defer():MySQL
    {

    }

    private function getConnectionName(?string $connectionName = null):string
    {
        if($connectionName){
            return $connectionName;
        }else{
            return "default";
        }
    }

    private function getConnectionPool(string $connectionName)
    {
        if(isset($this->config[$connectionName])){
            /** @var ConnectionConfig $conf */
            $conf = $this->config[$connectionName];
        }
    }

}