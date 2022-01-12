<?php

namespace EasySwoole\ORM\Db;

use EasySwoole\Pool\ObjectInterface;
use Swoole\Coroutine\MySQL;

class Object extends MySQL implements ObjectInterface
{

    function gc()
    {
        if($this->connected){
            $this->close();
        }
    }

    function objectRestore()
    {

    }

    function beforeUse(): ?bool
    {
        return $this->connected;
    }
}