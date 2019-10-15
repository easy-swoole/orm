<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\Component\Pool\Exception\PoolEmpty;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Exception\Exception;

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
                }
            }
            $result->setResult($ret);
            $result->setLastError($client->mysqlClient()->error);
            $result->setLastErrorNo($client->mysqlClient()->errno);
            $result->setLastInsertId($client->mysqlClient()->insert_id);
            $result->setAffectedRows($client->mysqlClient()->affected_rows);
            $builder->reset();
            if(in_array('SQL_CALC_FOUND_ROWS',$builder->getLastQueryOptions())){
                $count = $client->mysqlClient()->query('SELECT FOUND_ROWS() as count',$this->config->getTimeout());
                $result->setTotalCount($count[0]['count']);
            }
        }catch (\Throwable $throwable){
            throw $throwable;
        }finally{
            if($client->mysqlClient()->errno){
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
        if(!$this->pool){
            $this->pool = new Pool($this->config);
        }
        $client = $this->pool->defer($this->config->getTimeout());
        if($client){
            return $client;
        }else{
            throw new PoolEmpty("pool empty for host {$this->config->getHost()}");
        }
    }

    public function destroyPool()
    {
        if($this->pool){
            $this->pool->destroyPool();
            $this->pool = null;
        }
    }
}