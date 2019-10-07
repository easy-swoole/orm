<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\ConnectionInterface;
use EasySwoole\ORM\Driver\DriverInterface;
use EasySwoole\ORM\Driver\Result;
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
            return  $this->connections[$connectionName];
        }
        return null;
    }

    public function startTransaction($atomic = false,$connectionNames = 'default'):bool
    {

    }

    /*
     * 强制提交
     */
    public function commit($forceAtomic = false):bool
    {

    }

    public function rollback():bool
    {

    }

}