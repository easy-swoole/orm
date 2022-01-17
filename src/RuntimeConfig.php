<?php

namespace EasySwoole\ORM;

use EasySwoole\ORM\Db\MysqlClient;

class RuntimeConfig
{
    /** @var MysqlClient|null */
    private $client;

    /**
     * @var ConnectionConfig $connectionConfig
     */
    private $connectionConfig;

    function setConnectionConfig(ConnectionConfig $config):RuntimeConfig
    {
        $this->connectionConfig = $config;
        return $this;
    }

    function getConnectionConfig():ConnectionConfig
    {
        if($this->connectionConfig == null){
            $this->connectionConfig = DbManager::getInstance()->connectionConfig();
        }
        return $this->connectionConfig;
    }

    function getClient():MysqlClient
    {
        if($this->client){
            return $this->client;
        }else{
            return DbManager::getInstance()->defer($this->connectionConfig->getName());
        }
    }

    function setClient(MysqlClient $client):RuntimeConfig
    {
        $this->client = $client;
        return $this;
    }


}