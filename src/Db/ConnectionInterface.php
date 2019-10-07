<?php


namespace EasySwoole\ORM\Db;


interface ConnectionInterface
{
    /**
     * Execute prepare query
     * 请确保同一协程下是用同一个连接执行的sql
     * @param string $prepareSql
     * @param array $bindParams
     * @return Result|null
     */
    public function execPrepareQuery(string $prepareSql, array $bindParams = []): ?Result;

    /**
     * Execute unprepared query
     * 请确保同一协程下是用同一个连接执行的sql
     * @param string $query
     * @return Result|null
     */
    public function rawQuery(string $query): ?Result;

}