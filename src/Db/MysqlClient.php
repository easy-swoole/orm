<?php

namespace EasySwoole\ORM\Db;

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\ConnectionConfig;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\ExecuteFail;
use EasySwoole\ORM\Exception\PrepareFail;
use EasySwoole\ORM\RuntimeConfig;
use EasySwoole\Pool\ObjectInterface;
use Swoole\Coroutine\MySQL;

class MysqlClient extends MySQL implements ObjectInterface
{

    private $isInTransaction = false;

    private $connectionConfig;

    function setConnectionConfig(ConnectionConfig $config):MysqlClient
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

    function gc()
    {
        if($this->connected){
            $this->close();
        }
    }

    function objectRestore()
    {
        /**
         * 连接对象归还的时候，如果遇到存在事务，
         * 说明编程的时候漏了提交或者回滚，为避免生产脏数据，
         * 强制回滚，回滚失败则断开连接，释放事务
         */
        if($this->isInTransaction){
            $this->close();
            $this->connected = false;
        }
    }

    function beforeUse(): ?bool
    {
        return $this->connected;
    }

    /**
     * @param $timeout
     * @return bool
     * 重写父类方法，实现事务标记
     */
    function begin($timeout = null):bool
    {
        throw new ExecuteFail("transaction are forbid call for mysql client");
    }

    function commit($timeout = null):bool
    {
        throw new ExecuteFail("transaction are forbid call for mysql client");
    }

    function rollback($timeout = null)
    {
        throw new ExecuteFail("transaction are forbid call for mysql client");
    }

    function execQueryBuilder(QueryBuilder $builder, bool $raw = false, float $timeout = null):QueryResult
    {
        if($timeout == null){
            $this->getConnectionConfig()->getTimeout();
        }

        $this->errno = 0;
        $this->error = '';
        $this->insert_id = 0;
        $this->affected_rows = 0;
        
        $result = new QueryResult();

        if($raw){
            //事务兼容，禁止客户端直接调用语句
            $test = str_replace(" ",'',strtolower($builder->getLastQuery()));
            if($test === "starttransaction") {
                if($this->isInTransaction){
                    $ret = true;
                }else{
                    $ret = $this->query($builder->getLastQuery(),$timeout);
                    if($ret){
                        $this->isInTransaction = true;
                    }
                }
            }elseif($test === "commit"){
                if(!$this->isInTransaction){
                    $ret = true;
                }else{
                    $ret = $this->query($builder->getLastQuery(),$timeout);
                    if($ret){
                        $this->isInTransaction = false;
                    }
                }
            }elseif($test === "rollback"){
                if(!$this->isInTransaction){
                    $ret = true;
                }else{
                    $ret = $this->query($builder->getLastQuery(),$timeout);
                    if($ret){
                        $this->isInTransaction = false;
                    }
                }
            }else{
                $ret = $this->query($builder->getLastQuery(),$timeout);
            }
        }else{
            $stmt = $this->prepare($builder->getLastPrepareQuery());
            if($stmt){
                $ret = $stmt->execute($builder->getLastBindParams());
                if($ret === false && $this->errno){
                    $e = new ExecuteFail($this->error);
                    $e->setQueryBuilder($builder);
                    throw $e;
                }
            }else{
                $e = new PrepareFail($this->error);
                $e->setQueryBuilder($builder);
                throw $e;
            }
        }
        $result->setResult($ret);
        $result->setLastError($this->error);
        $result->setLastErrorNo($this->errno);
        $result->setLastInsertId($this->insert_id);
        $result->setAffectedRows($this->affected_rows);

        return $result;
    }
}