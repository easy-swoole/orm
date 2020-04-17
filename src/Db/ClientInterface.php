<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\QueryBuilder;

interface ClientInterface
{
    public function query(QueryBuilder $builder,bool $rawQuery = false): Result;

    public function lastQuery():? QueryBuilder;
    public function lastQueryResult():? Result;

    public function startTransaction();
    public function commit();
    public function rollback();
    public function setTransactionStatus(bool $bool);
}