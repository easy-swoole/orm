<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Pool\AbstractPool;

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

    function defer(float $timeout = null): ?ClientInterface
    {
        if($timeout === null){
            $timeout = $this->config->getGetObjectTimeout();
        }
        return $this->getPool()->defer($timeout);
    }

    function __getClientPool(): AbstractPool
    {
        return $this->getPool();
    }


    protected function getPool():MysqlPool
    {
        if(!$this->pool){
            $this->pool = new MysqlPool($this->config);
        }
        return $this->pool;
    }

    /**
     * @return Config|null
     */
    public function getConfig():?Config
    {
        return $this->config;
    }
}