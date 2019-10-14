<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\ConnectionInterface;
use Swoole\Coroutine;
use Throwable;

/**
 * Class DbManager
 * @package EasySwoole\ORM
 */
class DbManager
{
    use Singleton;

    protected $connections = [];
    protected $transactionContext = [];

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
     * 开启事务
     * @param string|array $connectionNames
     * @return bool
     */
    public function startTransaction($connectionNames = 'default'):bool
    {
        $successes = [];
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
            $con     = self::getConnection($name);
            $builder = new QueryBuilder();
            $builder->startTrans();
            $res = $con->query($builder, TRUE);

            if ($res->getResult() === true){
                $successes[] = $name;
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

    /**
     * @param null $connectName
     * @return bool
     */
    public function commit($connectName = NULL):bool
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid])){
            // 如果有指定
            if ($connectName !== NULL){
                $con     = self::getConnection($connectName);
                $builder = new QueryBuilder();
                $builder->commit();
                $res = $con->query($builder, TRUE);
                if ($res->getResult() !== true){
                    $this->rollback();
                    return false;
                }
                return true;
            }
            foreach ($this->transactionContext[$cid] as $name){
                $con     = self::getConnection($name);
                $builder = new QueryBuilder();
                $builder->commit();
                $res = $con->query($builder, TRUE);
                if ($res->getResult() !== true){
                    $this->rollback();
                    return false;
                }
            }
            unset($this->transactionContext[$cid]);
            return true;
        }
        return false;
    }

    /**
     * @param null $connectName
     * @return bool
     */
    public function rollback($connectName = NULL):bool
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid])){
            // 如果有指定
            if ($connectName !== NULL){
                $con     = self::getConnection($connectName);
                $builder = new QueryBuilder();
                $builder->rollback();
                $res = $con->query($builder, TRUE);
                if ($res->getResult() !== true){
                    $this->rollback();
                    return false;
                }
                return true;
            }
            foreach ($this->transactionContext[$cid] as $name){
                $con     = self::getConnection($name);
                $builder = new QueryBuilder();
                $builder->rollback();
                $res = $con->query($builder, TRUE);
                if ($res->getResult() !== true){
                    return false;
                }
            }
            unset($this->transactionContext[$cid]);
            return true;
        }
        return false;
    }

}