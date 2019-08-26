<?php


namespace EasySwoole\TpORM\Driver;


interface DriverInterface
{
    public function query():?Result;
}