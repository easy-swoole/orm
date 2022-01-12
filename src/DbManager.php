<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\ORM\Db\Pool;
use EasySwoole\ORM\Exception\PoolError;
use Swoole\Coroutine;
use Swoole\Coroutine\MySQL;

class DbManager
{
    use Singleton;

    /** @var callable|null */
    private $onQuery;

    protected $config = [];
    protected $pool = [];
    protected $context = [];


    function addConnection(ConnectionConfig $config):DbManager
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

    function invoke(callable $call,string $connectionName = "default",float $timeout = 3):MySQL
    {
        $obj = $this->getConnectionPool($connectionName)->getObj($timeout);
        if($obj){
            try{
                call_user_func($call,$obj);
            }catch (\Throwable $exception){
                throw $exception;
            }finally {
                $this->getConnectionPool($connectionName)->recycleObj($obj);
            }
        }else{
            throw new PoolError("connection: {$connectionName} getObj() timeout,pool may be empty");
        }
    }

    function defer(string $connectionName = "default",float $timeout = 3):MySQL
    {
        $id = Coroutine::getCid();
        if(isset($this->context[$id][$connectionName])){
            return $this->context[$id][$connectionName];
        }else{
            $obj = $this->getConnectionPool($connectionName)->defer($timeout);
            if($obj){
                $this->context[$id][$connectionName] = $obj;
                Coroutine::defer(function ()use($id){
                    unset($this->context[$id]);
                });
                return $obj;
            }else{
                throw new PoolError("connection: {$connectionName} defer() timeout,pool may be empty");
            }
        }
    }


    private function getConnectionPool(string $connectionName):Pool
    {
        if(isset($this->pool[$connectionName])){
            return $this->pool[$connectionName];
        }
        if(isset($this->config[$connectionName])){
            /** @var ConnectionConfig $conf */
            $conf = $this->config[$connectionName];
        }else{
            throw new PoolError("connection: {$connectionName} did not register yet");
        }
    }

}