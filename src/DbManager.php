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
                return false;
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

    function commit($atomic = false)
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionAtomicContext[$cid])){
            if($atomic == false || $this->transactionAtomicContext[$cid] == 1){
                foreach ($this->transactionConContext[$cid] as $con){
                    try{
                        $this->getConnection($con)->rawQuery('rollback');
                    }catch (\Throwable $exception){
                        trigger_error($exception->getMessage());
                    }
                }
                unset($this->transactionAtomicContext[$cid]);
                unset($this->transactionConContext[$cid]);;
            }else{
                $this->transactionAtomicContext[$cid]--;
            }
            return true;
        }else{
            return false;
        }
    }

    function rollback($atomic = false):bool
    {
        $cid = Coroutine::getCid();
        if(isset($this->transactionAtomicContext[$cid])){
            if($atomic == false || $this->transactionAtomicContext[$cid] == 1){
                foreach ($this->transactionConContext[$cid] as $con){
                    try{
                        $this->getConnection($con)->rawQuery('rollback');
                    }catch (\Throwable $exception){
                        trigger_error($exception->getMessage());
                    }
                }
                unset($this->transactionAtomicContext[$cid]);
                unset($this->transactionConContext[$cid]);;
            }else{
                $this->transactionAtomicContext[$cid]--;
            }
            return true;
        }else{
            return false;
        }
    }

    function transaction($call)
    {

    }
}