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

    function addConnection(Config $config):DbManager
    {
        $this->config[$config->getConnectionName()] = $config;
        return $this;
    }

    function getConnection(string $connectionName = 'default',float $timeout = null)
    {

    }

    public function execQuery(string $prepareSql,array $bindParams = [],float $timeout = null):?Result
    {

    }

    public function rawQuery(string $sql,float $timeout = null):?Result
    {

    }

    public function startTransaction():bool
    {

    }

    public function commit():bool
    {

    }

    public function rollback():bool
    {

    }

}