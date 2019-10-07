<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;
use EasySwoole\ORM\Db\Config;
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

    protected $config = [];
    protected $transactionContext = [];

    function addConnection(Config $config,string $connectionName = 'default'):DbManager
    {
        $this->config[$connectionName] = $config;
        return $this;
    }

    function getConnection(float $timeout = null,string $connectionName = 'default')
    {

    }

    public function execQuery(string $prepareSql,array $bindParams = [],float $timeout = null):?Result
    {

    }

    public function rawQuery(string $sql,float $timeout = null):?Result
    {

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