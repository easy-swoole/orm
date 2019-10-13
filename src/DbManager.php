<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
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

    public function startTransaction($connectionNames = 'default'):bool
    {
        $successes = [];
        if(!is_array($connectionNames)){
            $connectionNames = [$connectionNames];
        }
        /*
         * 1、 raw执行 start transaction
         * 2、若全部链接执行成功，则往transactionContext 标记对应协程的成功事务，并注册一个defer自动执行回滚,防止用户忘了提交导致死锁
         * 3、若部分链接成功，则成功链接执行rollback
         */
        Coroutine::defer(function (){
           $cid = Coroutine::getCid();
           if(isset($this->transactionContext[$cid])){
               $this->rollback();
           }
        });

    }

    public function commit():bool
    {
        Coroutine::defer(function (){
            $cid = Coroutine::getCid();
            if(isset($this->transactionContext[$cid])){
                /*
                 * raw执行 commit
                 */
                unset($this->transactionContext[$cid]);
            }
        });
    }

    public function rollback():bool
    {
        Coroutine::defer(function (){
            $cid = Coroutine::getCid();
            if(isset($this->transactionContext[$cid])){
                /*
                 * raw执行 rollback
                 */
                unset($this->transactionContext[$cid]);
            }
        });
    }

}