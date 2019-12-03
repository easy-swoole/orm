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
        $errno = 0;
        $error = '';
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

            $errno = $client->mysqlClient()->errno;
            $error = $client->mysqlClient()->error;
            $insert_id     = $client->mysqlClient()->insert_id;
            $affected_rows = $client->mysqlClient()->affected_rows;
            /*
             * 重置mysqli客户端成员属性，避免下次使用
             */
            $client->mysqlClient()->errno = 0;
            $client->mysqlClient()->error = '';
            $client->mysqlClient()->insert_id     = 0;
            $client->mysqlClient()->affected_rows = 0;
            //结果设置
            $result->setResult($ret);
            $result->setLastError($error);
            $result->setLastErrorNo($errno);
            $result->setLastInsertId($insert_id);
            $result->setAffectedRows($affected_rows);
        }catch (\Throwable $throwable){
            throw $throwable;
        }finally{
            if($errno){
                /*
                    * 断线的时候回收链接
                */
                if(in_array($errno,[2006,2013])){
                    $this->pool->unsetObj($client);
                }
                throw new Exception($error);
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
            throw new PoolEmpty("mysql pool empty at host:{$this->config->getHost()} port:{$this->config->getPort()} db:{$this->config->getDatabase()}");
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