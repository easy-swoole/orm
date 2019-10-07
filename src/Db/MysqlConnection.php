<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\Client;

class MysqlConnection implements ConnectionInterface
{
    /** @var Config */
    protected $config;
    protected $pool;
    function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execPrepareQuery(string $prepareSql, array $bindParams = []): ?Result
    {
        // TODO: Implement execPrepareQuery() method.
    }

    public function rawQuery(string $query): ?Result
    {
        // TODO: Implement rawQuery() method.
    }

    private function getClient():?Client
    {
        if(!$this->pool){
            $this->pool = new Pool($this->config);
        }
        return $this->pool->defer($this->config->getTimeout());
    }

}