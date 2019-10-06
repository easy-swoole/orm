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

    function addConnection(Config $config):DbManager
    {
        $this->config[$config->getConnectionName()] = $config;
        return $this;
    }

    function getConnection(string $connectionName = 'default',float $timeout = 5.0)
    {

    }

}