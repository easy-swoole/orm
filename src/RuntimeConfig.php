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
    private $order  = [];
    private $where  = [];
    private $join   = [];
    private $groupBy  = [];
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
            return DbManager::getInstance()->defer($this->getConnectionConfig()->getName());
        }
    }

    function setClient(MysqlClient $client):RuntimeConfig
    {
        $this->client = $client;
        return $this;
    }

    public function where(...$args)
    {
        $this->where = $args;
    }

    public function getWhere():array
    {
        return $this->where;
    }

    public function order(...$args)
    {
        $this->order[] = $args;
        return $this;
    }

    public function limit(int $one, ?int $two = null)
    {
        if ($two !== null) {
            $this->limit = [$one, $two];
        } else {
            $this->limit = $one;
        }
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function field($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $this->fields = $fields;
        return $this;
    }

    public function withTotalCount()
    {
        $this->withTotalCount = true;
        return $this;
    }

    public function getWithTotalCount():bool
    {
        return $this->withTotalCount;
    }

    public function getOrder():array
    {
        return $this->order;
    }

    public function getGroupBy():array
    {
        return $this->groupBy;
    }

    public function groupBy($filed)
    {
        $this->groupBy[] = $filed;
        return $this;
    }

    public function join(...$args)
    {
        $this->join[] = $args;
        return $this;
    }

    public function getJoin():array
    {
        return $this->join;
    }



    public function reset()
    {
        $this->fields = "*";
        $this->limit  = null;
        $this->withTotalCount = false;
        $this->order  = [];
        $this->where  = [];
        $this->join   = [];
        $this->groupBy  = [];
        $this->alias  = null;
    }

}