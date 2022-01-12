<?php

namespace EasySwoole\ORM;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\MysqlClient;

class QueryExecutor extends QueryBuilder
{
    /** @var MysqlClient|null */
    private $client;
    /** @var string|null */
    private $connectionName;

    private $timeout = 3;

    function setTimeout(float $time):QueryExecutor
    {
        $this->timeout = $time;
        return $this;
    }

    function setClient(MysqlClient $client):QueryExecutor
    {
        $this->client = $client;
        return $this;
    }

    function setConnectionName(string $name):QueryExecutor
    {
        $this->connectionName = $name;
        return $this;
    }


    function get($tableName, $numRows = null, $columns = null)
    {
        parent::get($tableName, $numRows, $columns);
        return DbManager::getInstance()->__exec($this->getClient(),$this,false,$this->timeout);
    }

    function update($tableName, $tableData, $numRows = null)
    {
        parent::update($tableName, $tableData, $numRows);
        return DbManager::getInstance()->__exec($this->getClient(),$this,false,$this->timeout);
    }

    function getOne($tableName, $columns = '*')
    {
        parent::getOne($tableName, $columns);
        return DbManager::getInstance()->__exec($this->getClient(),$this,false,$this->timeout);
    }

    function insert($tableName, $insertData)
    {
        parent::insert($tableName, $insertData);
        return DbManager::getInstance()->__exec($this->getClient(),$this,false,$this->timeout);
    }

    function insertAll($tableName, $insertData, $option = [])
    {
        parent::insertAll($tableName, $insertData, $option);
        return DbManager::getInstance()->__exec($this->getClient(),$this,false,$this->timeout);
    }

    private function getClient():MysqlClient
    {
        if($this->client){
            return $this->client;
        }else{
            return DbManager::getInstance()->defer($this->connectionName);
        }
    }
}