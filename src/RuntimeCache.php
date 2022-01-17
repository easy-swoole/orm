<?php

namespace EasySwoole\ORM;

use EasySwoole\Component\Singleton;

class RuntimeCache
{
    use Singleton;

    private $data = [];

    function set($key,$val)
    {
        $this->data[$key] = $val;
    }

    function get($key)
    {
        if(isset($this->data[$key])){
            return $this->data[$key];
        }
        return null;
    }

    function clear()
    {
        $this->data = [];
    }
}