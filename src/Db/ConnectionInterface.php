<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\QueryBuilder;

interface ConnectionInterface
{
    public function query(QueryBuilder $builder,bool $rawQuery = false):Result;
}