<?php


namespace EasySwoole\ORM\Driver;


interface DriverInterface
{
    public function query():?Result;
}