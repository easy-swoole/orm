<?php


namespace EasySwoole\ORM\Driver;


interface DriverInterface
{
    /*
     * 请确保同一协程下是用同一个连接执行的sql
     */
    public function prepareQuery(string $prepareSql, array $bindParams = []):?Result;

    public function rawQuery(string $query):?Result;
}