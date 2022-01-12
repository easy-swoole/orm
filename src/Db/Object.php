<?php

namespace EasySwoole\ORM\Db;

use EasySwoole\Pool\ObjectInterface;
use Swoole\Coroutine\MySQL;

class Object extends MySQL implements ObjectInterface
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
}