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

    /** @var string|array */
    private $fields = "*";
    /** @var null|array|int */
    private $limit  = null;
    private $withTotalCount = false;
    private $order  = null;
    private $where  = [];
    private $join   = null;
    private $group  = null;
    private $alias  = null;

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