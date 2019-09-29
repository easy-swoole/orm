<?php

namespace EasySwoole\ORM\Driver;

use EasySwoole\Component\Pool\Exception\PoolObjectNumError;
use EasySwoole\ORM\Exception\Exception;
use Swoole\Coroutine;
use Throwable;

/**
 * Class MysqlDriver
 * @package EasySwoole\ORM\Driver
 */
class MysqlDriver implements DriverInterface
{
    /**
     * Current connection config
     * @var MysqlConfig
     */
    private $config;

    /**
     * 用来确保一个协程内，获取的链接都是同一个
     * 不用pool管理器，因为存在多个链接
     * @var MysqlObject[]
     */
    private $mysqlContext = [];


    /**
     * 只初始化一次池对象
     * @var MysqlPool
     */
    private $pool;

    /**
     * MysqlDriver constructor.
     * @param MysqlConfig $config
     */
    function __construct(MysqlConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Execute prepare query
     * @param string $prepareSql
     * @param array $bindParams
     * @return Result|null
     * @throws Exception
     * @throws PoolObjectNumError
     * @throws Throwable
     */
    public function execPrepareQuery(string $prepareSql, array $bindParams = []): ?Result
    {
        $obj = $this->getConnection();
        if ($obj) {
            $result = new Result();
            $stmt = $obj->prepare($prepareSql, $this->config->getTimeout());
            if ($stmt) {
                $ret = $stmt->execute($bindParams);
                $result->setResult($ret);
            }
            $result->setLastError($obj->error);
            $result->setLastErrorNo($obj->errno);
            $result->setLastInsertId($obj->insert_id);
            $result->setAffectedRows($obj->affected_rows);
            return $result;
        } else {
            throw new Exception("mysql pool is empty");
        }
    }

    /**
     * Execute unprepared query
     * @param string $query
     * @return Result|null
     * @throws Exception
     * @throws PoolObjectNumError
     * @throws Throwable
     */
    public function rawQuery(string $query): ?Result
    {
        $obj = $this->getConnection();
        if ($obj) {
            $result = new Result();
            $ret = $obj->query($query, $this->config->getTimeout());
            $result->setResult($ret);
            $result->setLastError($obj->error);
            $result->setLastErrorNo($obj->errno);
            $result->setLastInsertId($obj->insert_id);
            $result->setAffectedRows($obj->affected_rows);
            return $result;
        } else {
            throw new Exception("mysql pool is empty");
        }
    }

    /**
     * 释放当前的池
     */
    public function destroyPool()
    {
        if ($this->pool) {
            $this->pool->destroyPool();
        }
    }

    /**
     * 从池中获取连接
     * @return MysqlObject|null
     * @throws PoolObjectNumError
     * @throws Throwable
     */
    private function getConnection(): ?MysqlObject
    {
        if (!$this->pool) {
            $this->pool = new MysqlPool($this->config);
        }
        $cid = Coroutine::getCid();
        if (!isset($this->mysqlContext[$cid])) {
            $this->mysqlContext[$cid] = $this->pool->getObj();;
            Coroutine::defer(function () use ($cid) {
                $this->pool->recycleObj($this->mysqlContext[$cid]);
            });
        }
        return $this->mysqlContext[$cid];
    }
}