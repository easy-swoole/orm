<?php

namespace EasySwoole\ORM;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\MysqlClient;
use EasySwoole\ORM\Db\QueryResult;

class QueryExecutor extends QueryBuilder
{
    /** @var MysqlClient|null */
    private $client;
    /** @var string|null */
    private $connectionName;

    private $timeout = 3;

    /** @var QueryResult */
    private $lastQueryResult;

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
        return $this->exec();
    }

    function update($tableName, $tableData, $numRows = null)
    {
        parent::update($tableName, $tableData, $numRows);
        return $this->exec();
    }

    function getOne($tableName, $columns = '*')
    {
        parent::getOne($tableName, $columns);
        return $this->exec();
    }

    function insert($tableName, $insertData)
    {
        parent::insert($tableName, $insertData);
        return $this->exec();
    }

    function insertAll($tableName, $insertData, $option = [])
    {
        parent::insertAll($tableName, $insertData, $option);
        return $this->exec();
    }

    private function getClient():MysqlClient
    {
        if($this->client){
            return $this->client;
        }else{
            return DbManager::getInstance()->defer($this->connectionName);
        }
    }

    private function exec()
    {
        $this->lastQueryResult = DbManager::getInstance()->__exec($this->getClient(),$this,false,$this->timeout);
        return $this->lastQueryResult->getResult();
    }
}