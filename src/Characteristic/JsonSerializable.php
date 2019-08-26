<?php


namespace EasySwoole\TpORM\Characteristic;


trait JsonSerializable
{
    use Base;

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function toArray():array
    {
        return $this->data;
    }
}