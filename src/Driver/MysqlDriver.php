<?php


namespace EasySwoole\ORM\Driver;


use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\ORM\Exception\Exception;
use Swoole\Coroutine;

class MysqlDriver implements DriverInterface
{
    private $config;
    /*
     * 用来确保一个协成内，获取的链接都是同一个
     * 不用pool 管理器，因为存在多个链接
     */
    private $mysqlContext = [];
    /**
     * @var AbstractPool
     */
    private $pool;

    function __construct(MysqlConfig $config)
    {
        $this->config = $config;
    }

    public function prepareQuery(string $prepareSql, array $bindParams = []): ?Result
    {

        $obj = $this->getCon();
        if($obj){
            $result = new Result();
            $stmt = $obj->prepare($prepareSql,$this->config->getTimeout());
            if($stmt){
                $ret = $stmt->execute($bindParams);
                $result->setResult($ret);
            }
            $result->setLastError($obj->error);
            $result->setLastErrorNo($obj->errno);
            $result->setLastInsertId($obj->insert_id);
            $result->setAffectedRows($obj->affected_rows);
            return $result;
        }else{
            throw new Exception("mysql pool is empty");
        }
    }

    public function rawQuery(string $query): ?Result
    {
        $obj = $this->getCon();
        if($obj){
            $result = new Result();
            $ret = $obj->query($query,$this->config->getTimeout());
            $result->setResult($ret);
            $result->setLastError($obj->error);
            $result->setLastErrorNo($obj->errno);
            $result->setLastInsertId($obj->insert_id);
            $result->setAffectedRows($obj->affected_rows);
            return $result;
        }else{
            throw new Exception("mysql pool is empty");
        }
    }

    public function destroyPool()
    {
        if($this->pool){
            $this->pool->destroyPool();
        }
    }

    private function getCon():?MysqlObject
    {
        if(!$this->pool){
            $this->pool = new MysqlPool($this->config);
        }
        $cid = Coroutine::getCid();
        if(!isset($this->mysqlContext[$cid])){
            $this->mysqlContext[$cid] = $this->pool->getObj();;
            Coroutine::defer(function ()use($cid){
                $this->pool->recycleObj($this->mysqlContext[$cid]);
            });
        }
        /** @var MysqlObject $obj */
        return $this->mysqlContext[$cid];
    }
}