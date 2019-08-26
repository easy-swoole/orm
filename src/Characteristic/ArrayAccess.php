<?php


namespace EasySwoole\TpORM\Characteristic;


trait ArrayAccess
{
    use Base;
    /*
     * ************ ArrayAccess *************
     */

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if(isset($this->data[$offset])){
            return $this->data[$offset];
        }else{
            return null;
        }
    }

    public function offsetSet($offset, $value):bool
    {
        if(!in_array($offset,$this->schemaInfo)){
            return false;
        }
        $this->data[$offset] = $value;
        return true;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}