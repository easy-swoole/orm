<?php

namespace EasySwoole\ORM\Concern;

use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Utility\PreProcess;
use EasySwoole\ORM\Utility\Schema\Table;
use EasySwoole\ORM\Utility\TableObjectGeneration;

trait Attribute
{
    use ConnectionInfo;

    /* 快速支持连贯操作 */
    private $fields = "*";
    private $limit  = NULL;
    private $withTotalCount = FALSE;
    private $order  = NULL;
    private $where  = [];
    private $join   = NULL;
    private $group  = NULL;
    private $alias  = NULL;
    /** @var string 表名 */
    protected $tableName = '';
    /** @var string 临时表名 */
    private $tempTableName = null;
    /** @var Table */
    private static $schemaInfoList;
    /** @var array 当前的数据 */
    private $data = [];
    /** @var array 附加数据 */
    private $_joinData = [];
    /** @var array 未应用修改器和获取器之前的原始数据 */
    private $originData;
    /** @var array toArray时候需要隐藏的字段 */
    private $hidden = [];

    /**
     * 表结构信息
     * @param bool $isCache
     * @return Table
     * @throws Exception
     */
    public function schemaInfo(bool $isCache = true): Table
    {
        $key = md5(static::class);
        if (isset(self::$schemaInfoList[$key]) && self::$schemaInfoList[$key] instanceof Table && $isCache == true) {
            return self::$schemaInfoList[$key];
        }
        if ($this->tempConnectionName) {
            $connectionName = $this->tempConnectionName;
        } else {
            $connectionName = $this->connectionName;
        }
        if(empty($this->tableName)){
            throw new Exception("Table name is require for model ".static::class);
        }
        $tableObjectGeneration = new TableObjectGeneration(DbManager::getInstance()->getConnection($connectionName), $this->tableName);
        $schemaInfo = $tableObjectGeneration->generationTable();
        self::$schemaInfoList[$key] = $schemaInfo;
        return self::$schemaInfoList[$key];
    }

    /**
     * ArrayAccess Exists
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getAttr($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return bool
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        return $this->setAttr($offset, $value);
    }


    /**
     * @param mixed $offset
     * @return bool
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        return $this->setAttr($offset, null);
    }

    /**
     * json序列化方法
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $return = [];
        foreach ($this->data as $key => $data){
            if (method_exists($this, $key)){
                $return[$key] = $this->data[$key];
            }else{
                $return[$key] = $this->getAttr($key);
            }
        }
        foreach ($this->_joinData as $key => $data)
        {
            $return[$key] = $data;
        }
        return $return;
    }

    /**
     * Model数据转数组格式返回
     * @param bool $notNul
     * @param bool $strict
     * @return array
     */
    public function toArray($notNul = false, $strict = true): array
    {
        $temp = [];
        foreach ($this->data as $key => $value){
            $temp[$key] = $this->getAttr($key);
        }

        if ($notNul) {
            foreach ($temp as $key => $value) {
                if ($value === null) {
                    unset($temp[$key]);
                }
            }
        }

        if (!$strict) {
            $temp = $this->reToArray($temp);
        }

        $temp = $this->filterData($temp);

        return $temp;
    }

    /**
     * 获取模型当前数据，不经过获取器
     * @param bool $notNul
     * @param bool $strict
     * @return array
     */
    public function toRawArray($notNul = false, $strict = true)
    {
        $tem = $this->data;
        if ($notNul){
            foreach ($this->data as $key => $value){
                if ($value !== null){
                    $tem[$key] = $value;
                }
            }
        }

        if (!$strict){
            $tem = $this->reToArray($tem);
        }

        $tem = $this->filterData($tem);

        return $tem;
    }

    protected function filterData($data)
    {
        if (is_array($this->fields)) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $this->fields)) {
                    unset($data[$key]);
                }
            }
            $this->fields = "*";
        }

        if (is_array($this->hidden)){
            foreach ($data as $key => $value) {
                if (in_array($key, $this->hidden)) {
                    unset($data[$key]);
                }
            }
            $this->hidden = [];
        }
        return $data;
    }

    /**
     * 循环处理附加数据的toArray
     * @param $temp
     * @return mixed
     */
    private function reToArray($temp)
    {
        foreach ($this->_joinData as $joinField => $joinData){
            if (is_object($joinData) && method_exists($joinData, 'toArray')){
                $temp[$joinField] = $joinData->toArray();
            }else{
                $joinDataTem = $joinData;
                if(is_array($joinData)){
                    $joinDataTem = [];
                    foreach ($joinData as $key => $one){
                        if (is_object($one) && method_exists($one, 'toArray')){
                            $joinDataTem[$key] = $one->toArray();
                        }else{
                            $joinDataTem[$key] = $one;
                        }
                    }
                }
                $temp[$joinField] = $joinDataTem;
            }
        }
        return $temp;
    }

    public function __toString()
    {
        $data = array_merge($this->data, $this->_joinData ?? []);
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $name
     * @param $value
     * @throws Exception
     */
    function __set($name, $value)
    {
        $this->setAttr($name, $value);
    }

    function __get($name)
    {
        return $this->getAttr($name);
    }

    public function __isset($name)
    {
        if (isset($this->data[$name])) return true;

        // 是否是附加字段
        if (isset($this->_joinData[$name])) return true;

        $method = 'get' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $name ) ) ) . 'Attr';
        if (method_exists($this, $method)) return true;

        // 判断是否有关联查询
        if (method_exists($this, $name)) return true;

        return false;
    }


    /**
     * 获取器
     * @param $attrName
     * @return mixed|null
     */
    public function getAttr($attrName)
    {
        $method = 'get' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
        if (method_exists($this, $method)) {
            return call_user_func([$this,$method],$this->data[$attrName] ?? null, $this->data);
        }
        // 判断是否有关联查询
        $notWhile = ['count', 'where', 'order', 'alias', 'join', 'with', 'max', 'min', 'avg','sum', 'field', 'get', 'all', 'delete', 'result'];
        if (method_exists($this, $attrName) && !in_array($attrName, $notWhile) ) {
            return $this->$attrName();
        }
        // 是否是附加字段
        if (isset($this->_joinData[$attrName])){
            return $this->_joinData[$attrName];
        }
        return $this->data[$attrName] ?? null;
    }

    /**
     * 设置器
     * @param $attrName
     * @param $attrValue
     * @param bool $setter
     * @return bool
     * @throws Exception
     */
    public function setAttr($attrName, $attrValue, $setter = true): bool
    {
        if (isset($this->schemaInfo()->getColumns()[$attrName])) {
            $col = $this->schemaInfo()->getColumns()[$attrName];
            $method = 'set' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
            if ($setter && method_exists($this, $method)) {
                $attrValue = call_user_func([$this,$method],$attrValue, $this->data);
            }
            $attrValue = PreProcess::dataValueFormat($attrValue, $col);
            $this->data[$attrName] = $attrValue;
            return true;
        } else {
            $this->_joinData[$attrName] = $attrValue;
            return false;
        }
    }

}