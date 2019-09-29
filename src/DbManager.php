<?php

namespace EasySwoole\ORM;

use EasySwoole\ORM\Driver\DriverInterface;
use EasySwoole\ORM\Driver\Result;
use Swoole\Coroutine;
use Throwable;

/**
 * Class DbManager
 * @package EasySwoole\ORM
 */
class DbManager
{
    /**
     * self instance
     * @var DbManager
     */
    private static $instance;

    /**
     * Registered Drivers
     * @var DriverInterface[]
     */
    private $connection = [];

    /**
     * Transaction hierarchy counter
     * @var array
     */
    private $transactionAtomicContext = [];

    /**
     * Connections in transactions
     * @var DriverInterface[]
     */
    private $transactionConContext = [];

    /**
     * onQuery Event
     * @var callable
     */
    private $onQuery;

    /**
     * afterQuery Event
     * @var callable
     */
    private $afterQeury;

    /**
     * Get instance
     * @return DbManager
     */
    public static function getInstance(): DbManager
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Add connection
     * @param DriverInterface $driver
     * @param string $connectionName
     * @return $this
     */
    function addConnection(DriverInterface $driver, string $connectionName = 'default')
    {
        $this->connection[$connectionName] = $driver;
        return $this;
    }

    /**
     * Get connection
     * @param string $connectionName
     * @return DriverInterface|null
     */
    function getConnection(string $connectionName = 'default'): ?DriverInterface
    {
        if (isset($this->connection[$connectionName])) {
            return $this->connection[$connectionName];
        } else {
            return null;
        }
    }

    /**
     * Start transaction
     * @param bool $atomic
     * @param array $connections
     * @return bool
     * @throws Throwable
     */
    function startTransaction($atomic = false, array $connections = ['default']): bool
    {
        $cid = Coroutine::getCid();
        if (!isset($this->transactionAtomicContext[$cid])) {
            try {
                $this->transactionAtomicContext[$cid] = 0;
                $this->transactionConContext[$cid] = [];
                foreach ($connections as $con) {
                    $ret = $this->rawQuery('start transaction', $con);
                    if (!$ret || $ret->getResult() != true) {
                        $this->rollback();
                        break;
                    } else {
                        $this->transactionConContext[$cid][] = $con;
                    }
                }
            } catch (Throwable $exception) {
                $this->rollback();
                throw $exception;
            }
            return true;
        }
        if ($atomic) {
            $this->transactionAtomicContext[$cid]++;
        } else {
            $this->transactionAtomicContext[$cid] = 1;
        }
        return true;
    }

    /**
     * Commit transaction
     * @param bool $atomic
     * @return bool
     */
    function commit(bool $atomic = false)
    {
        $cid = Coroutine::getCid();
        if (isset($this->transactionAtomicContext[$cid])) {
            if ($atomic == false || $this->transactionAtomicContext[$cid] == 1) {
                foreach ($this->transactionConContext[$cid] as $con) {
                    $ret = $this->rawQuery('commit', $con);
                    if ($ret && $ret->getResult()) {
                        unset($this->transactionConContext[$cid][$con]);
                    }
                }
                if (!empty($this->transactionConContext[$cid])) {
                    unset($this->transactionAtomicContext[$cid]);
                    unset($this->transactionConContext[$cid]);
                    return true;
                } else {
                    return false;
                }
            } else {
                $this->transactionAtomicContext[$cid]--;
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Rollback transaction
     * @param bool $atomic
     * @return bool
     */
    function rollback(bool $atomic = false): bool
    {
        $cid = Coroutine::getCid();
        if (isset($this->transactionAtomicContext[$cid])) {
            if ($atomic == false || $this->transactionAtomicContext[$cid] == 1) {
                foreach ($this->transactionConContext[$cid] as $con) {
                    $ret = $this->rawQuery('rollback', $con);
                    if ($ret && $ret->getResult()) {
                        unset($this->transactionConContext[$cid][$con]);
                    }
                }
                if (!empty($this->transactionConContext[$cid])) {
                    unset($this->transactionAtomicContext[$cid]);
                    unset($this->transactionConContext[$cid]);
                    return true;
                } else {
                    return false;
                }
            } else {
                $this->transactionAtomicContext[$cid]--;
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Automatic transaction
     * @param $call
     * @param array $connections
     * @return mixed|null
     * @throws Throwable
     */
    function transaction($call, $connections = ['default'])
    {
        if ($this->startTransaction(false, $connections)) {
            try {
                return call_user_func($call);
            } catch (Throwable $exception) {
                throw $exception;
            } finally {
                $this->rollback();
            }
        }
        return null;
    }

    /**
     * Execute prepare query
     * @param string $prepareSql
     * @param array $bindParams
     * @param string $connectionName
     * @return Result|null
     */
    public function execPrepareQuery(string $prepareSql, array $bindParams = [], string $connectionName = 'default'): ?Result
    {
        return $this->getConnection($connectionName)->execPrepareQuery($prepareSql, $bindParams);
    }

    /**
     * Execute unprepared query
     * @param string $query
     * @param string $connectionName
     * @return Result|null
     */
    public function rawQuery(string $query, string $connectionName = 'default'): ?Result
    {
        return $this->getConnection($connectionName)->rawQuery($query);
    }

    /**
     * 静态调用给出Builder
     * TODO 此处可以修改builder 给Builder对象注入connection 以实现 $builder->exec() 或语句结束后自动执行操作
     * @param $name
     * @param $arguments
     */
    public static function __callStatic($name, $arguments)
    {

    }
}