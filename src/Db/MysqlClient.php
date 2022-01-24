<?php

namespace EasySwoole\ORM\Db;

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\ConnectionConfig;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\ExecuteFail;
use EasySwoole\ORM\Exception\PrepareFail;
use EasySwoole\Pool\ObjectInterface;
use Swoole\Coroutine\MySQL;

class MysqlClient extends MySQL implements ObjectInterface
{

    private $debugTrace = [];

    private $isInTransaction = false;
    private $hasLock = false;

    private $connectionConfig;

    function getDebugTrace():array
    {
        return $this->debugTrace;
    }

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
        $this->secureCheck();
        if($this->connected){
            $this->close();
        }
    }

    function objectRestore()
    {
        $this->secureCheck();
        /**
         * 连接对象归还的时候，如果遇到存在事务，
         * 说明编程的时候漏了提交或者回滚，为避免生产脏数据，
         * 强制回滚，回滚失败则断开连接，释放事务
         */
        if($this->isInTransaction || $this->hasLock){
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
        $this->debugTrace[] = clone $builder;

        if($timeout == null){
            $this->getConnectionConfig()->getTimeout();
        }

        $this->errno = 0;
        $this->error = '';
        $this->insert_id = 0;
        $this->affected_rows = 0;
        
        $result = new QueryResult();

        if($raw){
            $ret = $this->query($builder->getLastQuery(),$timeout);
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

        $op = $builder->getLastTransactionOp();

        switch ($op){
            case QueryBuilder::TS_OP_START:{
                if($ret == true){
                    $this->isInTransaction = true;
                }
                break;
            }
            case QueryBuilder::TS_OP_ROLLBACK:
            case QueryBuilder::TS_OP_COMMIT:{
                if($ret){
                    $this->isInTransaction = false;
                }
                break;
            }

            case QueryBuilder::TS_OP_LOCK_TABLE:{
                if($ret){
                    $this->hasLock = true;
                }
                break;
            }
            case QueryBuilder::TS_OP_UNLOCK_TABLE:{
                if($ret){
                    $this->hasLock = false;
                }
                break;
            }

            case QueryBuilder::TS_OP_LOCK_IN_SHARE:
            case QueryBuilder::TS_OP_LOCK_FOR_UPDATE:{
                //执行后自动释放，不需要标记。
                break;
            }
        }

        return $result;
    }

    private function secureCheck()
    {
        //如果处于事务中
        try{
            if($this->isInTransaction || $this->hasLock){
                $call = DbManager::getInstance()->onSecureEvent();
                call_user_func($call,$this->debugTrace,$this->getConnectionConfig());
            }
        }catch (\Throwable $exception){
            $this->debugTrace = [];
        }
    }
}