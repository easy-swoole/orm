<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\ConnectionInterface;
use EasySwoole\ORM\Db\Result;
use EasySwoole\ORM\Exception\Exception;
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

    function query(QueryBuilder $builder,bool $raw = false,string $connectionName = 'default'):Result
    {
        $start = microtime(true);
        $con = $this->getConnection($connectionName);
        if($con){
            $ret = null;
            try{
                $ret = $con->query($builder,$raw);
                if(in_array('SQL_CALC_FOUND_ROWS',$builder->getLastQueryOptions())){
                    $temp = new QueryBuilder();
                    $temp->raw('SELECT FOUND_ROWS() as count');
                    $count = $con->query($temp,true);
                    if($this->onQuery){
                        call_user_func($this->onQuery,$count,$temp,$start);
                    }
                    $ret->setTotalCount($count->getResult()[0]['count']);
                }
            }catch (\Throwable $exception){
                throw $exception;
            }finally{
                if($this->onQuery){
                    $temp = clone $builder;
                    call_user_func($this->onQuery,$ret,$temp,$start);
                }
            }
            return $ret;
        }else{
            throw new Exception("connection : {$connectionName} not register");
        }
    }

    public function startTransaction($connectionNames = 'default'):bool
    {
        if(!is_array($connectionNames)){
            $connectionNames = [$connectionNames];
        }
        /*
         * 1、raw执行 start transaction
         * 2、若全部链接执行成功，则往transactionContext 标记对应协程的成功事务，并注册一个defer自动执行回滚,防止用户忘了提交导致死锁
         * 3、若部分链接成功，则成功链接执行rollback
         */
        $cid = Coroutine::getCid();
        foreach ($connectionNames as $name) {
            $builder = new QueryBuilder();
            $builder->starTtransaction();
            $res = $this->query($builder,true,$name);
            if ($res->getResult() === true){
                $this->transactionContext[$cid][] = $name;
            }else{
                $this->rollback();
                return false;
            }
        }

        Coroutine::defer(function (){
            $cid = Coroutine::getCid();
            if(isset($this->transactionContext[$cid])){
                $this->rollback();
            }
        });
        return true;
    }

    public function commit($connectName = NULL):bool
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid])){
            // 如果有指定
            if ($connectName !== NULL){
                $builder = new QueryBuilder();
                $builder->commit();
                $res = $this->query($builder,true,$connectName);
                if ($res->getResult() !== true){
                    $this->rollback();
                    return false;
                }
                $this->clearTransactionContext($connectName);
                return true;
            }
            foreach ($this->transactionContext[$cid] as $name){
                $builder = new QueryBuilder();
                $builder->commit();
                $res = $this->query($builder, true,$name);
                if ($res->getResult() !== true){
                    $this->rollback();
                    return false;
                }
            }
            $this->clearTransactionContext();
            return true;
        }
        return false;
    }


    public function rollback($connectName = NULL):bool
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid])){
            // 如果有指定
            if ($connectName !== NULL){
                $builder = new QueryBuilder();
                $builder->rollback();
                $res = $this->query($builder, true,$connectName);
                if ($res->getResult() !== true){
                    return false;
                }
                $this->clearTransactionContext($connectName);
                return true;
            }
            foreach ($this->transactionContext[$cid] as $name){
                $builder = new QueryBuilder();
                $builder->rollback();
                $res = $this->query($builder, true,$name);
                if ($res->getResult() !== true){
                    return false;
                }
            }
            $this->clearTransactionContext();
            return true;
        }
        return false;
    }

    protected function clearTransactionContext($connectName = null)
    {
        $cid = Coroutine::getCid();
        if (!isset($this->transactionContext[$cid])){
            return false;
        }

        if ($connectName !== null){
            foreach ($this->transactionContext[$cid] as $key => $name){
                if ($name === $connectName){
                    unset($this->transactionContext[$cid][$key]);
                    return true;
                }
                return false;
            }
        }

        unset($this->transactionContext[$cid]);
        return true;
    }

}