<?php


namespace EasySwoole\ORM;

use ArrayAccess;
use EasySwoole\Component\Pool\Exception\PoolObjectNumError;
use EasySwoole\DDL\Enum\DataType;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Exception\DriverNotFound;
use EasySwoole\ORM\Utility\PreProcess;
use EasySwoole\ORM\Utility\Schema\Table;
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

    /** @var Table */
    protected $schemaInfo;
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

    public function getSchemaInfo():Table
    {
        return $this->schemaInfo;
    }

    function __construct(array $data = [])
    {
        $this->schemaInfo = $this->schemaInfo();
        $this->data = $this->originData = PreProcess::dataFormat($data,$this,true);
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
        if (array_key_exists($this->data, $attrName)) {
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
     * @throws Throwable
     * @example AbstractModel::get(1)
     * @example AbstractModel::get([ 'whereProp' => 'whereVal' ])
     * @example AbstractModel::get(function( $Builder ){}) // auto limit 1
     */
    public function get($where = null)
    {
        $modelInstance = new static;
        $builder = new QueryBuilder;
        $builder =  PreProcess::mappingWhere($builder,$where,$modelInstance);
        $builder->getOne($modelInstance->getTableName());

        return $modelInstance;
    }

    protected function query(QueryBuilder $builder)
    {
        $con = DbManager::getInstance()->getConnection($this->connectionName);
        if($con){
            $ret = $con->execPrepareQuery($builder->getLastPrepareQuery(),$builder->getLastBindParams());
        }else{

        }

    }

    /**
     * 获取多条数据
     * @param $where
     * @return array
     * @throws DriverNotFound
     * @throws PoolObjectNumError
     * @throws Throwable
     * @example AbstractModel::all('1,2,3')
     * @example AbstractModel::all([1,2,3])
     * @example AbstractModel::all([ 'whereProp' => 'whereVal' ])
     * @example AbstractModel::all(function( $Builder ){})
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

    public static function create(array $data = []):AbstractModel
    {
        // TODO 转为模型的Save操作
        return new static($data);
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
     * @example Utility::destroy(1)
     * @example Utility::destroy('1,2,3')
     * @example Utility::destroy([1,2,3])
     * @example Utility::destroy([ 'whereProp' => 'whereVal' ])
     * @example Utility::destroy(function( $Builder ){})
     * @example Utility::destroy(1)
     */
    public static function destroy($where)
    {
        // TODO 没有条件不允许执行删除操作
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