<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config as MysqlConfig;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\AbstractPool;

class Pool extends AbstractPool
{
    protected function createObject()
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $client = new Client(new MysqlConfig($config->toArray()));
        if($client->connect()){
            return $client;
        }else{
            throw new Exception($client->mysqlClient()->connect_error);
        }
    }
}