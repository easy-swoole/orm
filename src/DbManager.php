<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Config;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\MysqlClient;
use EasySwoole\ORM\Db\Pool;
use EasySwoole\ORM\Db\QueryResult;
use EasySwoole\ORM\Exception\PoolError;
use EasySwoole\Pool\AbstractPool;
use Swoole\Coroutine;
use Swoole\Coroutine\Scheduler;
use Swoole\Timer;

class DbManager
{
    use Singleton;

    /** @var callable|null */
    private $onQuery;

    protected $config = [];
    protected $pool = [];
    /** @var callable|null */
    protected $onSecureEvent;

    function __construct()
    {
        $this->onSecureEvent = function (array $traces,ConnectionConfig $config){
            echo "connectionName [{$config->getName()}] for {$config->getHost()}:{$config->getPort()}@{$config->getUser()} may has un commit transaction or un release table lock:\n";
            /** @var QueryBuilder $trace */
            foreach ($traces as $trace){
                echo "\t".$trace->getLastQuery()."\n";
            }
        };
    }

    function onSecureEvent(?callable $call = null):?callable
    {
        if($call != null){
            $this->onSecureEvent = $call;
        }
        return $this->onSecureEvent;
    }

    function addConnection(ConnectionConfig $config):DbManager
    {
        $this->config[$config->getName()] = $config;
        return $this;
    }

    function connectionConfig(string $connectionName = "default"):ConnectionConfig
    {
        if(isset($this->config[$connectionName])){
            return $this->config[$connectionName];
        }else{
            throw new PoolError("connection: {$connectionName} did not register yet");
        }
    }

    function setOnQuery(?callable $func = null):?callable
    {
        if($func){
            $this->onQuery = $func;
        }
        return $this->onQuery;
    }

    function fastQuery(?string $connectionName = "default"):QueryExecutor
    {
        if(isset($this->config[$connectionName])){
            return (new QueryExecutor())->setConnectionConfig($this->config[$connectionName]);
        }else{
            throw new PoolError("connection: {$connectionName} did not register yet");
        }
    }

    function invoke(callable $call,string $connectionName = "default",float $timeout = null)
    {
        if($timeout == null){
            $this->config[$connectionName]->getTimeout();
        }
        $obj = $this->getConnectionPool($connectionName)->getObj($timeout);
        if($obj){
            try{
                return call_user_func($call,$obj);
            }catch (\Throwable $exception){
                throw $exception;
            }finally {
                $this->getConnectionPool($connectionName)->recycleObj($obj);
            }
        }else{
            throw new PoolError("connection: {$connectionName} getObj() timeout,pool may be empty");
        }
    }

    function defer(string $connectionName = "default",?float $timeout = null):MysqlClient
    {
        $obj = $this->getConnectionPool($connectionName)->defer($timeout);
        if($obj){
            return $obj;
        }else{
            throw new PoolError("connection: {$connectionName} defer() timeout,pool may be empty");
        }
    }

    function __exec(MysqlClient $client,QueryBuilder $builder,bool $raw = false,?float $timeout = null):QueryResult
    {
        $start = microtime(true);
        $result = $client->execQueryBuilder($builder,$raw,$timeout);
        if($this->onQuery){
            $temp = clone $builder;
            call_user_func($this->onQuery,$result,$temp,$start,$client);
        }
        if(in_array('SQL_CALC_FOUND_ROWS',$builder->getLastQueryOptions())){
            $temp = new QueryBuilder();
            $temp->raw('SELECT FOUND_ROWS() as count');
            $count = $client->execQueryBuilder($temp,false,$timeout);
            if($this->onQuery){
                call_user_func($this->onQuery,$count,$temp,$start,$client);
            }
            $result->setTotalCount($count->getResult()[0]['count']);
        }
        return $result;
    }


    public function startTransaction(?MysqlClient $client = null):bool
    {
        $query = new QueryBuilder();
        $query->raw('start transaction');
        if($client == null){
            $client = $this->defer();
        }
        return $this->__exec($client,$query,true)->getResult();
    }

    public function commit(?MysqlClient $client = null):bool
    {
        $query = new QueryBuilder();
        $query->raw('commit');
        if($client == null){
            $client = $this->defer();
        }
        return $this->__exec($client,$query,true)->getResult();
    }

    public function rollback(?MysqlClient $client = null):bool
    {
        $query = new QueryBuilder();
        $query->raw("rollback");
        if($client == null){
            $client = $this->defer();
        }
        return $this->__exec($client,$query,true)->getResult();
    }



    function resetPool(bool $clearTimer = true)
    {
        /**
         * @var  $key
         * @var AbstractPool $pool
         */
        foreach ($this->pool as $key => $pool){
            $pool->reset();
        }
        $this->pool = [];
        if($clearTimer){
            Timer::clearAll();
        }
    }

    function runInMainProcess(callable $func,bool $clearTimer = true)
    {
        $scheduler = new Scheduler();
        $scheduler->add(function ()use($func,$clearTimer){
            $func($this);
            $this->resetPool($clearTimer);
        });
        $scheduler->start();

    }

    private function getConnectionPool(string $connectionName):Pool
    {
        if(isset($this->pool[$connectionName])){
            return $this->pool[$connectionName];
        }
        $conf = $this->connectionConfig($connectionName);
        $pool = new Pool($conf);
        $this->pool[$connectionName] = $pool;
        return $pool;
    }

}