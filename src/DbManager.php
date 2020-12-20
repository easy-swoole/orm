<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\ClientInterface;
use EasySwoole\ORM\Db\ConnectionInterface;
use EasySwoole\ORM\Db\Result;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;
use Swoole\Coroutine;

/**
 * Class DbManager
 * @package EasySwoole\ORM
 */
class DbManager
{
    use Singleton;

    protected $connections = [];
    protected $transactionContext = [];
    protected $transactionCountContext = [];
    protected $lastQueryContext = [];
    protected $onQuery;

    public function onQuery(callable $call):DbManager
    {
        $this->onQuery = $call;
        return $this;
    }

    function addConnection(ConnectionInterface $connection,string $connectionName = 'default'):DbManager
    {
        $this->connections[$connectionName] = $connection;
        return $this;
    }

    function getConnection(string $connectionName = 'default'):?ConnectionInterface
    {
        if(isset($this->connections[$connectionName])){
            return $this->connections[$connectionName];
        }
        return null;
    }

    /**
     * @param string $connectionName
     * @param float|NULL $timeout
     * @return ClientInterface
     * @throws Exception
     * @throws PoolEmpty
     * @throws \Throwable
     */
    protected function getClient(string $connectionName,float $timeout = null):ClientInterface
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid][$connectionName])){
            return $this->transactionContext[$cid][$connectionName];
        }
        $connection = $this->getConnection($connectionName);
        if($connection instanceof ConnectionInterface){
            $client = $connection->__getClientPool()->getObj($timeout);
            if($client){
                if($client instanceof ClientInterface){
                    //做名称标记
                    $client->connectionName($connectionName);
                    return $client;
                }else{
                    throw new Exception("connection : {$connectionName} pool not a EasySwoole\ORM\Db\ConnectionInterface pool");
                }
            }else{
                throw new PoolEmpty("connection : {$connectionName} is empty");
            }
        }else{
            throw new Exception("connection : {$connectionName} not register");
        }
    }

    /**
     * 回收客户端
     * @param string $connectionName
     * @param ClientInterface|null $client
     * @throws \Throwable
     */
    protected function recycleClient(string $connectionName,?ClientInterface $client)
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid][$connectionName])){
            //处于事务中的连接暂时不回收
            return;
        }else if($client){
            $this->getConnection($connectionName)->__getClientPool()->recycleObj($client);
        }
    }

    function getLastQuery():?QueryBuilder
    {
        $cid = Coroutine::getCid();
        if(isset($this->lastQueryContext[$cid])){
            return $this->lastQueryContext[$cid];
        }
        return null;
    }

    /**
     * @param QueryBuilder $builder
     * @param bool $raw
     * @param string|ClientInterface $connection
     * @param float|null $timeout
     * @return Result
     * @throws Exception
     * @throws \Throwable
     */
    function query(QueryBuilder $builder, bool $raw = false, $connection = 'default', float $timeout = null):Result
    {
        $cid = Coroutine::getCid();
        if(!isset($this->lastQueryContext[$cid])){
            Coroutine::defer(function ()use($cid){
                unset($this->lastQueryContext[$cid]);
            });
        }
        $this->lastQueryContext[$cid] = $builder;

        $name = null;
        if(is_string($connection)){
            $name = $connection;
            $client = $this->getClient($connection,$timeout);
        }else if($connection instanceof ClientInterface){
            $client = $connection;
        }else{
            throw new Exception('var $connection not a connectionName or ClientInterface');
        }
        try{
            $start = microtime(true);
            $ret = $client->query($builder,$raw);
            if($this->onQuery){
                $temp = clone $builder;
                call_user_func($this->onQuery,$ret,$temp,$start);
            }
            if(in_array('SQL_CALC_FOUND_ROWS',$builder->getLastQueryOptions())){
                $temp = new QueryBuilder();
                $temp->raw('SELECT FOUND_ROWS() as count');
                $count = $client->query($temp,true);
                if($this->onQuery){
                    call_user_func($this->onQuery,$count,$temp,$start,$client);
                }
                $ret->setTotalCount($count->getResult()[0]['count']);
            }
            return $ret;
        }catch (\Throwable $exception){
            throw $exception;
        } finally {
            if($name){
                $this->recycleClient($name,$client);
            }
        }
    }

    /**
     * @param callable $call
     * @param string $connectionName
     * @param float|NULL $timeout
     * @return mixed|void
     * @throws Exception
     * @throws PoolEmpty
     * @throws \Throwable
     */
    function invoke(callable $call,string $connectionName = 'default',float $timeout = null)
    {
        $client = $this->getClient($connectionName,$timeout);
        if($client){
            try{
                return call_user_func($call,$client);
            }catch (\Throwable $exception){
                throw $exception;
            }finally{
                $this->recycleClient($connectionName,$client);
            }
        }
        return ;
    }

    /**
     * @param string|ClientInterface $con
     * @param float|null $timeout
     * @return bool
     * @throws Exception
     * @throws PoolEmpty
     * @throws \Throwable
     */
    public function startTransaction($con = 'default',float $timeout = null):bool
    {
        $defer = true;
        $name = null;
        $client = null;
        if($con instanceof ClientInterface){
            $client = $con;
            $name = $client->connectionName();
            $defer = false;
        }else{
            $name = $con;
        }
        $cid = Coroutine::getCid();
        //检查上下文
        if(isset($this->transactionContext[$cid][$name])){
            return true;
        }
        if(!$client){
            $client = $this->getClient($name,$timeout);
        }
        try{
            $builder = new QueryBuilder();
            $builder->startTransaction();
            $ret = $this->query($builder,true,$client);
            //外部连接不需要帮忙注册defer清理，需要外部注册者自己做。
            if($ret->getResult() && $defer){
                $client->__inTransaction = true;
                $this->transactionContext[$cid][$name] = $client;
                Coroutine::defer(function (){
                    $this->transactionDeferExit();
                });
            }
            return $ret->getResult();
        }catch (\Throwable $exception){
            $this->recycleClient($name,$client);
            throw $exception;
        }
    }

    /**
     * @param string $con
     * @param float|NULL $timeout
     * @return bool
     * @throws Exception
     * @throws PoolEmpty
     * @throws \Throwable
     */
    public function startTransactionWithCount($con = 'default', float $timeout = null)
    {
        if($con instanceof ClientInterface){
            $client = $con;
            $name = $client->connectionName();
        }else{
            $name = $con;
        }

        $cid = Coroutine::getCid();

        if (!isset($this->transactionCountContext[$cid][$name]) || $this->transactionCountContext[$cid][$name] === 0){
            $this->transactionCountContext[$cid][$name] = 1;
            return $this->startTransaction($con, $timeout);
        }

        $this->transactionCountContext[$cid][$name]++;
        return true;
    }

    /**
     * @param $con
     * @return bool
     * @throws \Throwable
     */
    public function commitWithCount($con = 'default')
    {
        if($con instanceof ClientInterface){
            $client = $con;
            $name = $client->connectionName();
        }else{
            $name = $con;
        }

        $cid = Coroutine::getCid();
        $this->transactionCountContext[$cid][$name]--;

        if ($this->transactionCountContext[$cid][$name] === 0){
            return $this->commit($con);
        }
        return true;
    }

    /**
     * @param string $con
     * @param float|NULL $timeout
     * @return bool
     * @throws \Throwable
     */
    public function rollbackWithCount($con = 'default',float $timeout = null)
    {
        if($con instanceof ClientInterface){
            $client = $con;
            $name = $client->connectionName();
        }else{
            $name = $con;
        }

        $cid = Coroutine::getCid();
        $this->transactionCountContext[$cid][$name]--;
        if ($this->transactionCountContext[$cid][$name] === 0){
            return $this->rollback($con, $timeout);
        }
        return true;
    }

    /**
     * @param string $con
     * @return bool
     * @throws \Throwable
     */
    public function commit($con = 'default'):bool
    {
        $outSideClient = false;
        $cid = Coroutine::getCid();
        $name = null;
        $client = null;
        if($con instanceof ClientInterface){
            $client = $con;
            $name = $client->connectionName();
            $outSideClient = true;
        }else{
            $name = $con;
            //没有上下文说明没有声明连接事务
            if(!isset($this->transactionContext[$cid][$name])){
                return true;
            }else{
                $client = $this->transactionContext[$cid][$name];
            }
        }
        try{
            $builder = new QueryBuilder();
            $builder->commit();
            $ret = $this->query($builder,true,$client);
            if($ret->getResult() && !$outSideClient){
                $client->__inTransaction = false;
                unset($this->transactionContext[$cid][$name]);
                $this->recycleClient($name,$client);
            }
            return $ret->getResult();
        }catch (\Throwable $exception){
            throw $exception;
        } finally {
            if(isset($this->transactionContext[$cid][$name])){
                $client = $this->transactionContext[$cid][$name];
                unset($this->transactionContext[$cid][$name]);
                $this->recycleClient($name, $client);
            }
        }
    }

    /**
     * @param string $con
     * @param float|NULL $timeout
     * @return bool
     * @throws \Throwable
     */
    public function rollback($con = 'default',float $timeout = null):bool
    {
        $outSideClient = false;
        $cid = Coroutine::getCid();
        $name = null;
        $client = null;
        if($con instanceof ClientInterface){
            $client = $con;
            $name = $client->connectionName();
            $outSideClient = true;
        }else{
            $name = $con;
            //没有上下文说明没有声明连接事务
            if(!isset($this->transactionContext[$cid][$name])){
                return true;
            }else{
                $client = $this->transactionContext[$cid][$name];
            }
        }
        try{
            $builder = new QueryBuilder();
            $builder->rollback();
            $ret = $this->query($builder,true,$client, $timeout);
            if($ret->getResult() && !$outSideClient){
                $client->__inTransaction = false;
                unset($this->transactionContext[$cid][$name]);
                $this->recycleClient($name,$client);
            }
            return $ret->getResult();
        }catch (\Throwable $exception){
            throw $exception;
        } finally {
            if(isset($this->transactionContext[$cid][$name])){
                $client = $this->transactionContext[$cid][$name];
                unset($this->transactionContext[$cid][$name]);
                $this->recycleClient($name, $client);
            }
        }
    }

    /**
     * @throws \Throwable
     */
    protected function transactionDeferExit()
    {

        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid])){
            /** @var ClientInterface $con */
            foreach ($this->transactionContext[$cid] as $con){
                $res = false;
                try{
                    // 回滚里会删除上下文 下面可以正常回收
                    $res = $this->rollback($con);
                }catch (\Throwable $exception){
                    throw $exception;
                } finally {
                    if(!$res){// 在rollback里会回收客户端了
                        //如果这个阶段的回滚还依旧失败，则废弃这个连接
                        $this->getConnection($con->connectionName())->__getClientPool()->unsetObj($con);
                    }
                }
            }
        }
        unset($this->transactionContext[$cid]);
    }


    public static function isInTransaction(ClientInterface $client):bool
    {
        if(isset($client->__inTransaction)){
            return (bool)$client->__inTransaction;
        }else{
            return false;
        }
    }
}
