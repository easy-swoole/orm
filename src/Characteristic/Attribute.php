<?php


namespace EasySwoole\TpORM\Characteristic;


trait Attribute
{
    use Base;

    function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    function __get($name)
    {
        if(isset($this->data[$name])){
            return $this->data[$name];
        }
        return null;
    }
}