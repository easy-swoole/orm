<?php

namespace EasySwoole\ORM;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\MysqlClient;
use EasySwoole\ORM\Db\QueryResult;

class QueryExecutor extends QueryBuilder
{
    /** @var MysqlClient|null */
    private $client;

    /**
     * @var ConnectionConfig $connectionConfig
     */
    private $connectionConfig;

    function setConnectionConfig(ConnectionConfig $config):QueryExecutor
    {
        $this->connectionConfig = $config;
        return $this;
    }

    /** @var QueryResult|null */
    private $lastQueryResult;

    function lastQueryResult():?QueryResult
    {
        return $this->lastQueryResult;
    }

    function setClient(MysqlClient $client):QueryExecutor
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

    function delete($tableName, $numRows = null)
    {
        parent::delete($tableName, $numRows);
        return $this->exec();
    }

    function getOne($tableName, $columns = '*')
    {
        parent::getOne($tableName, $columns);
        $ret = $this->exec();
        if($ret){
            return $ret[0];
        }
        return null;
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

    function execRaw($sql, $param = [])
    {
        parent::raw($sql, $param);
        return $this->exec(true);
    }

    public function startTransaction()
    {
        parent::startTransaction();
        return $this->exec(true);
    }

    public function commit()
    {
        parent::commit();
        return $this->exec(true);
    }

    public function rollback()
    {
        parent::rollback();
        return $this->exec(true);
    }

    public function lockTable($table)
    {
        parent::lockTable($table);
        return $this->exec(true);
    }

    public function unlockTable()
    {
        parent::unlockTable();
        return $this->exec(true);
    }

    private function getClient():MysqlClient
    {
        if($this->client){
            return $this->client;
        }else{
            return DbManager::getInstance()->defer($this->connectionConfig->getName());
        }
    }

    private function exec(bool $raw = false)
    {
        $this->lastQueryResult = DbManager::getInstance()->__exec($this->getClient(),$this,$raw);
        return $this->lastQueryResult->getResult();
    }
}