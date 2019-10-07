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
        $client = $this->getClient();
        if($client){
            try{
                $stmt = $client->mysqlClient()->prepare($prepareSql,$this->config->getTimeout());
            }catch (\Throwable $throwable){
                /*
                 * 如果发现是断线了的，回收链接
                 */
            }

        }else{

        }
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