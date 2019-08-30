<?php


namespace EasySwoole\ORM;


use EasySwoole\ORM\Driver\DriverInterface;
use Swoole\Coroutine;

class DbManager
{
    private static $instance;

    private $con = [];
    private $transactionAtomicContext = [];
    private $transactionConContext = [];

    public static function getInstance():DbManager
    {
        if(!isset(self::$instance)){
            self::$instance = new static();
        }
        return self::$instance;
    }

    function addConnection(DriverInterface $driver,string $name = 'default')
    {
        $this->con[$name] = $driver;
        return $this;
    }

    function getConnection(string $name = 'default'):?DriverInterface
    {
        if(isset($this->con[$name])){
            return $this->con[$name];
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
                    $ret = $this->getConnection($con)->rawQuery('start transaction');
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
                    $ret = $this->getConnection($con)->rawQuery('commit');
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
                    $ret = $this->getConnection($con)->rawQuery('rollback');
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
}