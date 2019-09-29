<?php


namespace EasySwoole\ORM;

use ArrayAccess;
use EasySwoole\Component\Pool\Exception\PoolObjectNumError;
use EasySwoole\DDL\Enum\DataType;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Driver\DriverInterface;
use EasySwoole\ORM\Driver\MysqlDriver;
use EasySwoole\ORM\Exception\DriverNotFound;
use EasySwoole\ORM\Model\Schema\Table;
use Exception;
use JsonSerializable;
use Throwable;

/**
 * 抽象模型
 * Class AbstractModel
 * @package EasySwoole\ORM
 */
abstract class AbstractModel implements ArrayAccess, JsonSerializable
{
    /**
     * 当前连接的驱动类
     * @var MysqlDriver
     */
    private $connectionDriver;

    /**
     * 当前连接驱动类的名称
     * 继承后可以覆盖该成员以指定默认的驱动类
     * @var string
     */
    protected $connectionName = 'default';

    /**
     * 当前的数据
     * @var array
     */
    protected $data;

    /**
     * 模型的原始数据
     * 未应用修改器和获取器之前的原始数据
     * @var array
     */
    protected $originData;

    /**
     * 返回当前模型的结构信息
     * 请为当前模型编写正确的结构
     * @return Table
     */
    abstract protected function schemaInfo(): Table;

    /**
     * AbstractModel constructor.
     * @param array $data 模型的初始数据
     * @param string $connectionName
     * @throws DriverNotFound
     * @throws Exception
     */
    function __construct(array $data = [], $connectionName = null)
    {
        if ($connectionName) {
            $this->connectionName = $connectionName;
        }

        $this->connectionDriver = DbManager::getInstance()->getConnection($this->connectionName);

        // 先把当前的驱动取出到模型中以便后续进行操作
        if (!$this->connectionDriver instanceof DriverInterface) {
            $ex = new DriverNotFound('ORM Driver ' . $connectionName . ' not found.');
            $ex->setDriverName($connectionName);
            throw $ex;
        }

        // 记录当前模型的数据以及原始数据
        $this->data = $this->originData = $this->_processDataFormat($data, true);
    }

    /**
     * 进行一次原生查询
     * @param string $sql
     * @param array $bindParams
     * @param bool $returnResultObject
     * @return Driver\Result|null
     * @throws Exception
     * @throws PoolObjectNumError
     * @throws Throwable
     */
    public function query(string $sql, array $bindParams = [], $returnResultObject = false)
    {
        $queryResult = $this->connectionDriver->execPrepareQuery($sql, $bindParams);
        if ($queryResult->getLastErrorNo()) {
            throw new Exception($queryResult->getLastError(), $queryResult->getLastErrorNo());
        } else {
            $resultCollection = $queryResult->getResult();
            return $returnResultObject ? $queryResult : $resultCollection;
        }
    }

    /**
     * 获取当前表的PK字段
     * @return mixed|null
     */
    public function getTablePk()
    {
        return $this->schemaInfo()->getPkFiledName();
    }

    /**
     * 获取当前的表名称
     * @return mixed
     */
    public function getTableName()
    {
        return $this->schemaInfo()->getTable();
    }

    /**
     * 获取字段值
     * TODO 应用获取器和自动完成字段
     * @param string $attrName
     * @return mixed
     */
    public function getAttr($attrName)
    {
        return $this->data[$attrName] ?? null;
    }

    /**
     * 设置字段值
     * TODO 应用修改器和自动完成字段
     * @param $attrName
     * @param $attrValue
     * @throws Exception
     */
    public function setAttr($attrName, $attrValue)
    {
        $processData = $this->_processDataFormat([$attrName => $attrValue]);
        if (array_key_exists($processData, $attrName)) {
            $this->data[$attrValue] = $processData[$attrName];
        }
    }

    /**
     * 设置模型数据
     * @param array $data
     * @return AbstractModel
     * @throws Exception
     */
    public function data(array $data)
    {
        $this->data = $this->originData = $this->_processDataFormat($data, true);
        return $this;
    }

    /**
     * 删除当前模型数据
     * TODO 如果当前PK有值则进行删除
     */
    public function delete()
    {

    }

    /**
     * 保存数据
     * TODO 当前data与originData脏检测
     * 如果有脏数据则执行对应操作
     * 没有脏数据说明数据已到最终状态
     */
    public function save()
    {
        // TODO 脏检测后根据PK来决定当前是插入操作还是更新操作
    }

    /**
     * 获取一条数据
     * @param $where
     * @return static
     * @throws DriverNotFound
     * @throws PoolObjectNumError
     * @throws Throwable
     * @example Model::get(1)
     * @example Model::get([ 'whereProp' => 'whereVal' ])
     * @example Model::get(function( $Builder ){}) // auto limit 1
     */
    public static function get($where = null)
    {
        $modelInstance = new static;
        $builder = new QueryBuilder;
        $builder = $modelInstance->_processWhere($where, $builder);

        $builder->getOne($modelInstance->getTableName());
        $result = $modelInstance->query($builder->getLastPrepareQuery(), $builder->getLastBindParams(), true);
        $modelInstance->data($result->getResult());
        return $modelInstance;
    }

    /**
     * 获取多条数据
     * @param $where
     * @return array
     * @throws DriverNotFound
     * @throws PoolObjectNumError
     * @throws Throwable
     * @example Model::all('1,2,3')
     * @example Model::all([1,2,3])
     * @example Model::all([ 'whereProp' => 'whereVal' ])
     * @example Model::all(function( $Builder ){})
     */
    public static function all($where = null)
    {
        $modelInstance = new static;
        $builder = new QueryBuilder;
        $builder = $modelInstance->_processWhere($where, $builder);

        $builder->get($modelInstance->getTableName());
        $results = $modelInstance->query($builder->getLastPrepareQuery(), $builder->getLastBindParams(), true);

        $resultSet = [];
        if ($results->getResult()) {
            foreach ($results->getResult() as $result) {
                $resultSet[] = new static($result);
            }
        }
        return $resultSet;
    }

    /**
     * 创建一条新的记录
     * @param array $data
     * @example Model::create(['userName'=>'userName'])
     */
    public static function create(array $data = [])
    {
        // TODO 转为模型的Save操作
    }

    /**
     * 更新当前记录
     * @param array $data
     * @param array $where
     */
    public static function update(array $data = [], array $where = [])
    {
        // TODO 转为模型的Save操作 -> isUpdate
    }

    /**
     * 删除表中的记录
     * @param $where
     * @example Model::destroy(1)
     * @example Model::destroy('1,2,3')
     * @example Model::destroy([1,2,3])
     * @example Model::destroy([ 'whereProp' => 'whereVal' ])
     * @example Model::destroy(function( $Builder ){})
     * @example Model::destroy(1)
     */
    public static function destroy($where)
    {
        // TODO 没有条件不允许执行删除操作
    }

    /**
     * 执行一次直接查询
     * 直接查询不会返回模型对象
     * @param string $sql
     * @param array $bindParams
     * @param bool $returnResultObject
     * @return Driver\Result|mixed|null
     * @throws DriverNotFound
     * @throws Exception
     * @throws PoolObjectNumError
     * @throws Throwable
     */
    public static function rawQuery(string $sql, array $bindParams = [], $returnResultObject = false)
    {
        $modelInstance = new static;
        $queryResult = $modelInstance->query($sql, $bindParams, $returnResultObject);
        return $queryResult;
    }

    /**
     * 处理数据集的格式
     * 数据入模型之前 通过此方法处理字段格式 以及字段过滤
     * @param array $data 需要设置的数据
     * @param bool $isInitValue 初始化会将没传入的字段设置为null
     * @return array
     * @throws Exception
     */
    private function _processDataFormat(array $data, $isInitValue = false)
    {
        $tempData = [];
        foreach ($this->schemaInfo()->getColumns() as $columnName => $column) {

            if (isset($data[$columnName])) {
                $tempData[$columnName] = $this->_processValueFormat($data[$columnName], $column->getColumnType());
            } else {
                $isInitValue && $tempData[$columnName] = null;
            }

        }

        return $tempData;
    }

    /**
     * 处理定义值的格式
     * @param mixed $data
     * @param integer $dataType
     * @return float|int|mixed|string
     * @throws Exception
     */
    private function _processValueFormat($data, $dataType)
    {
        if (DataType::typeIsTextual($dataType)) {
            return strval($data);
        } else {
            return $data;
        }
    }

    /**
     * 快速处理Where参数以支持多种格式的条件
     * @param mixed $whereProps
     * @param QueryBuilder $builder
     * @return QueryBuilder
     * @throws Exception
     */
    public function _processWhere($whereProps, $builder)
    {
        // 处理查询条件
        $primaryKey = $this->getTablePk();
        if (is_int($whereProps)) {
            if (empty($primaryKey)) {
                throw  new Exception('Table not have primary key, so can\'t use Model::get($pk)');
            } else {
                $builder->where($primaryKey, $whereProps);
            }
        } else if (is_string($whereProps)) {
            $whereKeys = explode(',', $whereProps);
            $builder->where($primaryKey, $whereKeys, 'IN');
        } else if (is_array($whereProps)) {
            // 如果不相等说明是一个键值数组 需要批量操作where
            if (array_keys($whereProps) !== range(0, count($whereProps) - 1)) {
                foreach ($whereProps as $whereFiled => $whereProp) {
                    if (is_array($whereProp)) {
                        $builder->where($whereFiled, ...$whereProps);
                    } else {
                        $builder->where($whereFiled, $whereProp);
                    }
                }
            } else {  // 否则是一个索引数组 表示查询主键
                $builder->where($primaryKey, $whereProps, 'IN');
            }
        } else if (is_callable($whereProps)) {
            $whereProps($builder);
        }
        return $builder;
    }

    /**
     * ArrayAccess Exists
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess Get
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttr($offset);
    }

    /**
     * ArrayAccess Set
     * @param mixed $offset
     * @param mixed $value
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        return $this->setAttr($offset, $value);
    }

    /**
     * ArrayAccess Unset
     * @param mixed $offset
     * @return void
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        return $this->setAttr($offset, null);
    }

    /**
     * jsonSerialize Data
     * TODO 批量应用获取器
     * @return mixed|void
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * toArray
     * TODO 批量应用获取器
     * @param bool $notNul
     * @return array
     */
    public function toArray($notNul = false): array
    {
        if ($notNul) {
            $temp = $this->data;
            foreach ($temp as $key => $value) {
                if ($value === null) {
                    unset($temp[$key]);
                }
            }
            return $temp;
        }
        return $this->data;
    }

    /**
     * __toString
     * TODO 批量应用获取器
     */
    public function __toString()
    {
        return json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 设置一个值
     * @param $name
     * @param $value
     * @throws Exception
     */
    function __set($name, $value)
    {
        $this->setAttr($name, $value);
    }

    /**
     * 获取一个值
     * @param $name
     * @return mixed|null
     */
    function __get($name)
    {
        return $this->getAttr($name);
    }
}