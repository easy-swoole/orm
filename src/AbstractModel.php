<?php

namespace EasySwoole\ORM;

use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\MysqlClient;

abstract class AbstractModel
{
    /** @var RuntimeConfig */
    private $runtimeConfig;

    abstract function tableName():string;

    function runtimeConfig(?RuntimeConfig $config = null):RuntimeConfig
    {
        if($config == null){
            if($this->runtimeConfig == null){
                $this->runtimeConfig = new RuntimeConfig();
            }
        }else{
            $this->runtimeConfig = $config;
        }
        return $this->runtimeConfig;
    }

    function schemaInfo(bool $refreshCache = false):Table
    {
        $key = md5(static::class.$this->tableName().$this->runtimeConfig()->getConnectionConfig()->getName());
        $client = $this->runtimeConfig->getClient();
        $query = new QueryBuilder();
        $query->raw("show full columns from {$this->tableName()}");

        $ret = DbManager::getInstance()->__exec($this->runtimeConfig()->getClient(),$query,false,$this->runtimeConfig->getConnectionConfig()->getTimeout());
    }



}