<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2019/10/22 0022
 * Time: 15:31
 */

include "./vendor/autoload.php";

defined("MYSQL_CONFIG") ?: define('MYSQL_CONFIG', [
    'host'          => '127.0.0.1',
    'port'          => 3306,
    'user'          => 'demo',
    'password'      => '123456',
    'database'      => 'demo',
    'timeout'       => 5,
    'charset'       => 'utf8mb4',
]);

go(function (){

    $config = new \EasySwoole\ORM\Db\Config(MYSQL_CONFIG);
    $connection = new \EasySwoole\ORM\Db\Connection($config);

    \EasySwoole\ORM\DbManager::getInstance()->addConnection($connection);

    $testUserModel = new \EasySwoole\ORM\Tests\TestUserModel();
    $testUserModel->state=1;
    $testUserModel->name='仙士可';
    $testUserModel->age=100;
    $testUserModel->addTime = date('Y-m-d H:i:s');

    $data = $testUserModel->save();
    var_dump($data);
});