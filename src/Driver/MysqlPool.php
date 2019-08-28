<?php


namespace EasySwoole\ORM\Driver;


use EasySwoole\Component\Pool\AbstractPool;

class MysqlPool extends AbstractPool
{
    protected function createObject()
    {
        $client = new MysqlObject();
        if($client->connect($this->getConfig()->toArray())){
            return $client;
        }else{
            return null;
        }
    }
}