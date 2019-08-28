<?php


namespace EasySwoole\ORM\Characteristic;


trait JsonSerializable
{
    protected $data = [];

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function toArray():array
    {
        return $this->data;
    }
}