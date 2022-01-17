<?php

namespace EasySwoole\ORM\Db;

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Exception\ExecuteFail;
use EasySwoole\ORM\Exception\PrepareFail;
use EasySwoole\Pool\ObjectInterface;
use Swoole\Coroutine\MySQL;

class MysqlClient extends MySQL implements ObjectInterface
{

    private $isInTransaction = false;

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
            $res = $this->rollback();
            if(!$res){
                $this->close();
            }
        }
    }

    function beforeUse(): ?bool
    {
        return $this->connected;
    }

    function startTransaction($timeout = null):bool
    {
        if($this->isInTransaction){
            return true;
        }
        $res = $this->begin($timeout);
        if($res){
            $this->isInTransaction = true;
        }
        return $res;
    }

    /**
     * @param $timeout
     * @return bool
     * 重写父类方法，实现事务标记
     */
    function begin($timeout = null):bool
    {
        if($this->isInTransaction){
            return true;
        }
        $res = parent::begin($timeout);
        if($res){
            $this->isInTransaction = true;
        }
        return $res;
    }

    function commit($timeout = null):bool
    {
        if($this->isInTransaction){
            $res = parent::commit($timeout);
            if($res){
                $this->isInTransaction = false;
            }
            return $res;
        }
        return false;

    }

    function rollback($timeout = null)
    {
        if($this->isInTransaction){
            $res = parent::rollback($timeout);
            if($res){
                $this->isInTransaction = false;
            }
            return $res;
        }
        return false;
    }

    function execQueryBuilder(QueryBuilder $builder, bool $raw = false, float $timeout = null):QueryResult
    {

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

        return $result;
    }
}