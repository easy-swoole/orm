<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config as MysqlConfig;
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
            return null;
        }
    }
}