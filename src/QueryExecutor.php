<?php

namespace EasySwoole\ORM;

use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\QueryBuilder;

class QueryExecutor extends QueryBuilder
{

    private $client;

    function setClient(Client $client):QueryExecutor
    {
        $this->client = $client;
        return $this;
    }

    function get($tableName, $numRows = null, $columns = null): ?QueryBuilder
    {
         parent::get($tableName, $numRows, $columns);
         $sql = $this->getLastPrepareQuery();
    }

    function update($tableName, $tableData, $numRows = null)
    {
        return parent::update($tableName, $tableData, $numRows);
    }

    function getOne($tableName, $columns = '*'): ?QueryBuilder
    {
        return parent::getOne($tableName, $columns);
    }

    function insert($tableName, $insertData)
    {
        return parent::insert($tableName, $insertData);
    }

    function insertAll($tableName, $insertData, $option = [])
    {
        return parent::insertAll($tableName, $insertData, $option);
    }

    function insertMulti($tableName, array $multiInsertData, array $dataKeys = null)
    {
        return parent::insertMulti($tableName, $multiInsertData, $dataKeys);
    }
}