<?php


namespace EasySwoole\ORM\Characteristic;


use EasySwoole\ORM\Utility\Column;

trait Attribute
{
    private $data = [];
    private $strict = false;

    function __set($name, $value)
    {
        if($this->strict){
            if(isset($this->schemaInfo[$name])){
                $this->data[$name] = Column::valueMap($value,$this->schemaInfo[$name]);
            }
        }else{
            $this->data[$name] = $value;
        }
    }

    function __get($name)
    {
        if(isset($this->data[$name])){
            return $this->data[$name];
        }
        return null;
    }
}