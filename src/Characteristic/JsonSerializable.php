<?php


namespace EasySwoole\ORM\Characteristic;


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