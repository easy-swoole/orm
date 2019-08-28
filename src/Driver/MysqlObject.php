<?php


namespace EasySwoole\ORM\Driver;


use EasySwoole\Component\Pool\PoolObjectInterface;
use Swoole\Coroutine\MySQL;

class MysqlObject extends MySQL implements PoolObjectInterface
{

    function gc()
    {

    }

    function objectRestore()
    {
        if($this->connected){
            $this->close();
        }
    }

    function beforeUse(): bool
    {
        return (bool)$this->connected;
    }
}