<?php


namespace EasySwoole\ORM\Driver;


interface DriverInterface
{
    public function query(string $prepareSql,array $bindParams = []):?Result;
}