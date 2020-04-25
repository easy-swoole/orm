<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\QueryBuilder;

interface ClientInterface
{
    public function query(QueryBuilder $builder,bool $rawQuery = false): Result;
    public function startTransaction():bool ;
    public function commit():bool;
    public function rollback():bool;
}