<?php


namespace EasySwoole\ORM\Driver;


use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\ORM\Exception\Exception;
use Swoole\Coroutine;

class MysqlDriver implements DriverInterface
{
    private $config;
    /**
     * @var AbstractPool
     */
    private $pool;
    function __construct(MysqlConfig $config)
    {
        $this->config = $config;
    }

    public function query(string $prepareSql, array $bindParams = []): ?Result
    {
        $result = new Result();
        if(!$this->pool){
            $this->pool = new MysqlPool($this->config);
        }
        /** @var MysqlObject $obj */
        $obj = $this->pool->getObj();
        if($obj){
            $stmt = $obj->prepare($prepareSql,$this->config->getTimeout());
            if($stmt){
                $ret = $stmt->execute($bindParams);
                $result->setResult($ret);
            }
            Coroutine::defer(function ()use($obj){
                $this->pool->recycleObj($obj);
            });
            $result->setLastError($obj->error);
            $result->setLastErrorNo($obj->errno);
            $result->setLastInsertId($obj->insert_id);
            $result->setAffectedRows($obj->affected_rows);
        }else{
            throw new Exception("mysql pool is empty");
        }
        return $result;
    }

    public function destroyPool()
    {
        if($this->pool){
            $this->pool->destroyPool();
        }
    }
}