<?php


namespace EasySwoole\ORM;


use EasySwoole\ORM\Driver\DriverInterface;
use EasySwoole\ORM\Driver\Result;
use Swoole\Coroutine;

class DbManager
{
    private static $instance;

    private $con = [];
    private $transactionAtomicContext = [];
    private $transactionConContext = [];
    /** @var callable */
    private $onQuery;
    /** @var callable */
    private $afterQeury;

    public static function getInstance():DbManager
    {
        if(!isset(self::$instance)){
            self::$instance = new static();
        }
        return self::$instance;
    }

    function addConnection(DriverInterface $driver,string $conName = 'default')
    {
        $this->con[$conName] = $driver;
        return $this;
    }

    function getConnection(string $conName = 'default'):?DriverInterface
    {
        if(isset($this->con[$conName])){
            return $this->con[$conName];
        }else{
            return null;
        }
    }

    function startTransaction($atomic = false,array $cons = ['default']):bool
    {
        $cid = Coroutine::getCid();
        if(!isset($this->transactionAtomicContext[$cid])){
            try{
                $this->transactionAtomicContext[$cid] = 0;
                $this->transactionConContext[$cid] = [];
                foreach ($cons as $con){
                    $ret = $this->rawQuery('start transaction',$con);
                    if(!$ret || $ret->getResult() != true){
                        $this->rollback();
                        break;
                    }else{
                        $this->transactionConContext[$cid][] = $con;
                    }
                }
            }catch (\Throwable $exception){
                $this->rollback();
                throw $exception;
            }
            return true;
        }
        if($atomic){
            $this->transactionAtomicContext[$cid]++;
        }else{
            $this->transactionAtomicContext[$cid] = 1;
        }
        return true;
    }

    function commit(bool $atomic = false)
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionAtomicContext[$cid])){
            if($atomic == false || $this->transactionAtomicContext[$cid] == 1){
                foreach ($this->transactionConContext[$cid] as $con){
                    $ret = $this->rawQuery('commit',$con);
                    if($ret && $ret->getResult()){
                        unset($this->transactionConContext[$cid][$con]);
                    }
                }
                if(!empty($this->transactionConContext[$cid])){
                    unset($this->transactionAtomicContext[$cid]);
                    unset($this->transactionConContext[$cid]);
                    return true;
                }else{
                    return false;
                }
            }else{
                $this->transactionAtomicContext[$cid]--;
                return true;
            }
        }else{
            return false;
        }
    }

    function rollback(bool $atomic = false):bool
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionAtomicContext[$cid])){
            if($atomic == false || $this->transactionAtomicContext[$cid] == 1){
                foreach ($this->transactionConContext[$cid] as $con){
                    $ret = $this->rawQuery('rollback',$con);
                    if($ret && $ret->getResult()){
                        unset($this->transactionConContext[$cid][$con]);
                    }
                }
                if(!empty($this->transactionConContext[$cid])){
                    unset($this->transactionAtomicContext[$cid]);
                    unset($this->transactionConContext[$cid]);
                    return true;
                }else{
                    return false;
                }
            }else{
                $this->transactionAtomicContext[$cid]--;
                return true;
            }
        }else{
            return false;
        }
    }

    function transaction($call,$cons = ['default'])
    {
        if($this->startTransaction(false,$cons)){
            try{
               return call_user_func($call);
            }catch (\Throwable $exception){
                throw $exception;
            }finally{
                $this->rollback();
            }
        }
        return null;
    }

    public function execPrepareQuery(string $prepareSql, array $bindParams = [],string $conName = 'default'):?Result
    {
        return $this->getConnection($conName)->execPrepareQuery($prepareSql,$bindParams);
    }

    public function rawQuery(string $query,string $conName = 'default'):?Result
    {
        return $this->getConnection($conName)->rawQuery($query);
    }
}