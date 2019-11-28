<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Exception\PoolEmpty;

class Connection implements ConnectionInterface
{
    /** @var Config */
    protected $config;
    /** @var AbstractPool */
    protected $pool;

    function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function query(QueryBuilder $builder,bool $rawQuery = false): Result
    {
        $result = new Result();
        $client = $this->getClient();
        $ret = null;
        try{
            if($rawQuery){
                $ret = $client->rawQuery($builder->getLastQuery(),$this->config->getTimeout());
            }else{
                $stmt = $client->mysqlClient()->prepare($builder->getLastPrepareQuery(),$this->config->getTimeout());
                if($stmt){
                    $ret = $stmt->execute($builder->getLastBindParams(),$this->config->getTimeout());
                }else{
                    $ret = false;
                }
            }

            if ($client->mysqlClient()->errno !== 0){
                $errno = $client->mysqlClient()->errno;
                $error = $client->mysqlClient()->error;
                $client->mysqlClient()->errno = 0;
                $client->mysqlClient()->error = "";
            }else{
                $errno = $client->mysqlClient()->errno;
                $error = $client->mysqlClient()->error;
            }

            $insert_id     = $client->mysqlClient()->insert_id;
            $affected_rows = $client->mysqlClient()->affected_rows;

            $client->mysqlClient()->insert_id     = 0;
            $client->mysqlClient()->affected_rows = 0;

            $result->setResult($ret);
            $result->setLastError($error);
            $result->setLastErrorNo($errno);
            $result->setLastInsertId($insert_id);
            $result->setAffectedRows($affected_rows);

        }catch (\Throwable $throwable){
            throw $throwable;
        }finally{
            if($ret === false && $client->mysqlClient()->errno){
                if(in_array($client->mysqlClient()->errno,[2006,2013])){
                    $this->pool->unsetObj($client);
                }
                throw new Exception("{$client->mysqlClient()->error}");
            }
        }
        return $result;
    }

    private function getClient():Client
    {
        $pool = $this->getPool();
        $client = $pool->defer($this->config->getTimeout());
        if($client){
            return $client;
        }else{
            throw new PoolEmpty("pool empty for host {$this->config->getHost()}");
        }
    }

    public function getPool():Pool
    {
        if(!$this->pool){
            $this->pool = new Pool($this->config);
        }
        return $this->pool;
    }
}