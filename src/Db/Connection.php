<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\Mysqli\Client;

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

    public function execPrepareQuery(string $prepareSql, array $bindParams = []): ?Result
    {
        $client = $this->getClient();
        if($client){
            try{
                $stmt = $client->mysqlClient()->prepare($prepareSql,$this->config->getTimeout());
                $ret = null;
                if($stmt){
                    $ret = $stmt->execute($bindParams,$this->config->getTimeout());
                }
            }catch (\Throwable $throwable){

            }finally{
                /*
                 * 如果发现是断线了的，回收链接
                 * 2006  2013
                 */
                if(in_array($client->mysqlClient()->errno,[2006,2013])){
                    $this->pool->unsetObj($client);
                }
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