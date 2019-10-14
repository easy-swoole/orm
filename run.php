<?php

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Tests\UserModel;
use Swoole\Coroutine;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\Db\Config;

require_once 'vendor/autoload.php';

// Add default connection
$config = new Config();
$config->setDatabase('employees');
$config->setUser('root');
$config->setPassword('47f8a4c02a960d78');
$config->setHost('192.168.23.128');

DbManager::getInstance()->addConnection(new Connection($config));

// Use connection in coroutine
Coroutine::create(function () {
    $res = UserModel::create()->get(4);
    var_dump($res->id);

    $res->age = 5;
    $strat = DbManager::getInstance()->startTransaction();
    var_dump($strat);

    $res = $res->update();
    var_dump($res);

    // $rollback = DbManager::getInstance()->rollback();
    // var_dump($rollback);

    // 返回false 因为连接已经回滚。事务关闭。
    $commit = DbManager::getInstance()->commit();
    var_dump($commit);
});