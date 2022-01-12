<?php

namespace EasySwoole\ORM;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Exception\ExecuteFail;
use EasySwoole\ORM\Exception\PrepareFail;
use Swoole\Coroutine\MySQL;

class QueryExecutor extends QueryBuilder
{
    /** @var MySQL|null */
    private $client;

    function setClient(MySQL $client):QueryExecutor
    {
        $this->client = $client;
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

    private function getClient():MySQL
    {
        if($this->client){
            return $this->client;
        }
    }

    private function exec(){

    }
}