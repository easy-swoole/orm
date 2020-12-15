<?php

namespace EasySwoole\ORM\Concern;

use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Db\ClientInterface;
use EasySwoole\ORM\Db\ConnectionInterface;
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
    private $lock = false;
    private $resetQuery = true;
    private $duplicate = [];
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
    /** @var array toArray时候追加显示的字段 */
    private $append = [];
    /** @var array toArray时候需要显示的字段 */
    private $visible = [];

    /** @var bool $toArrayNotNull toArray参数 */
    protected $toArrayNotNull = false;
    /** @var bool $toArrayScript toArray参数 */
    protected $toArrayStrict = true;

    /** @var array 类型映射设置 */
    protected $casts = [];

    /**
     * 表结构信息
     * @param bool $isCache
     * @return Table
     * @throws Exception
     */
    public function schemaInfo(bool $isCache = true): Table
    {
        // 是否有注入client
        if($this->client instanceof ClientInterface){
            $connectionName = $this->client->connectionName();
        }else{
            if ($this->tempConnectionName) {
                $connectionName = $this->tempConnectionName;
            } else {
                $connectionName = $this->connectionName;
            }
        }

        $connection = DbManager::getInstance()->getConnection($connectionName);
        if (!$connection instanceof ConnectionInterface) {
            throw new Exception("connection : {$connectionName} not register");
        }

        // 使用连接名+表名做key  在分库、分表的时候需要使用
        $key = md5("{$connectionName}_{$this->tableName()}");
        if (isset(self::$schemaInfoList[$key]) && self::$schemaInfoList[$key] instanceof Table && $isCache == true) {
            return self::$schemaInfoList[$key];
        }
        if(empty($this->tableName())){
            throw new Exception("Table name is require for model ".static::class);
        }
        $tableObjectGeneration = new TableObjectGeneration($connection, $this->tableName(),$this->client);
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
        return $this->toArray(false, false);
    }

    /**
     * Model数据转数组格式返回
     * @param bool|null $notNull
     * @param bool|null $strict
     * @return array
     */
    public function toArray($notNull = null, $strict = null): array
    {
        if ($notNull === null) {
            $notNull = $this->toArrayNotNull;
        }

        if ($strict === null) {
            $strict = $this->toArrayStrict;
        }

        $temp = [];
        foreach ($this->data as $key => $value){
            $temp[$key] = $this->getAttr($key);
        }

        if ($notNull) {
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
     * @param bool|null $notNul
     * @param bool|null $strict
     * @return array
     */
    public function toRawArray($notNull = null, $strict = null)
    {
        if ($notNull === null) {
            $notNull = $this->toArrayNotNull;
        }

        if ($strict === null) {
            $strict = $this->toArrayStrict;
        }

        $tem = $this->data;
        if ($notNull){
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

    /**
     * toArray时 执行数据过滤
     * @param $data
     * @return mixed
     */
    protected function filterData($data)
    {
        /** fields 是临时筛选显示用 */
        if (is_array($this->fields)) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $this->fields)) {
                    unset($data[$key]);
                }
            }
            $this->fields = "*";
        }
        /** 筛选显示 */
        if (is_array($this->visible) && !empty($this->visible) ){
            foreach ($data as $key => $value) {
                if (!in_array($key, $this->visible)) {
                    unset($data[$key]);
                }
            }
        }
        /** 筛选隐藏 */
        if (is_array($this->hidden) && !empty($this->hidden) ){
            foreach ($data as $key => $value) {
                if (in_array($key, $this->hidden)) {
                    unset($data[$key]);
                }
            }
        }
        // 追加非模型字段的属性，必须设置获取器。
        if (is_array($this->append) && !empty($this->append) ){
            foreach ($this->append as $appendKey){
                $method = 'get' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $appendKey ) ) ) . 'Attr';
                if (method_exists($this, $method)){
                    $data[$appendKey] = call_user_func([$this,$method], null, $this->data);
                }
            }
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

        if ( method_exists($this, $name) ) return true;

        return false;
    }

    /**
     * @return array
     */
    public function getOriginData(): array
    {
        return $this->originData;
    }

    /**
     * 获取属性：获取器 > 原始字段 > 附加字段 > 关联查询
     * @tip 关联查询的名字不可以为关键字
     * @param $attrName
     * @return mixed|null
     */
    public function getAttr($attrName)
    {
        $method = 'get' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
        if (method_exists($this, $method)) {
            return call_user_func([$this,$method],$this->data[$attrName] ?? null, $this->data);
        }
        if (isset($this->data[$attrName])) return $this->preCast($attrName, $this->data[$attrName]);
        if (isset($this->_joinData[$attrName])) return $this->preCast($attrName, $this->_joinData[$attrName]);

        // 判断是否有关联查询
        // 这里不用担心关联查询的时候会触发基类方法，用户在定义的时候就会冲突了，如query(){ }

        if ( method_exists($this, $attrName) && !method_exists(AbstractModel::class, $attrName) ) {
            return $this->$attrName();
        }
        return null;
    }

    /**
     * 设置属性
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

            if ($setter) {
                $attrValue = $this->preCastToJson($attrName, $attrValue);
            }

            $attrValue = PreProcess::dataValueFormat($attrValue, $col);

            $this->data[$attrName] = $attrValue;
            return true;
        } else {
            if ($setter) {
                $attrValue = $this->preCastToJson($attrName, $attrValue);
            }
            $this->_joinData[$attrName] = $attrValue;
            return false;
        }
    }

    /**
     * 预先转换cast为json字符
     * 如果设置的cast是数字、对象等类型，不可以直接储存在数据库，需要转换为json字符串
     * @param $attrName
     * @param $attrValue
     * @return mixed
     * @throws Exception
     */
    private function preCastToJson($attrName, $attrValue)
    {
        if ($this->isJsonCast($attrName) && ! is_null($attrValue)) {
            $attrValue = $this->castAttributeAsJson($attrName, $attrValue);
        }
        return $attrValue;
    }
    /**
     * 判断是否json类型cast
     * @param $key
     * @return bool
     */
    private function isJsonCast($key)
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * 预先转换cast设置
     * @param $key
     * @param $value
     * @return mixed
     */
    private function preCast($key, $value)
    {
        if ($this->hasCast($key)){
            $type = $this->casts[$key];
            switch ($type){
                case 'int':
                case 'integer':
                    return (int) $value;
                case 'real':
                case 'float':
                case 'double':
                    return (float) $value;
                case 'string':
                    return (string) $value;
                case 'bool':
                case 'boolean':
                    return (bool) $value;
                case 'array':
                    $temp =  $this->fromJson($value, true);
                    return $temp;
                case 'object':
                case 'json':
                    return $this->fromJson($value);
                case 'date':// todo date需要扩展为 date:Y-m-d... 自定义转换格式
                    return $this->asDate($value);
                case 'datetime':
                    return $this->asDateTime($value);
                case 'timestamp':
                    return $this->asTimestamp($value);
                    // todo 支持小数，并且定义小数后几位 decimal:<digits>
                default:
                    return $value;
            }
        }
        return $value;
    }

    /**
     * 判断是否设置cast
     * @param $key
     * @param array $types
     * @return bool
     */
    private function hasCast($key, $types = [])
    {
        if (!empty($types) && is_array($types)){
            return isset($this->casts[$key]) && in_array($this->casts[$key], $types);
        }
        return isset($this->casts[$key]);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     * @throws Exception
     */
    private function castAttributeAsJson($key, $value)
    {
        $value = $this->asJson($value);

        if ($value === false) {
            $error = json_last_error_msg() ?? "";
            throw new Exception("{$key} cast to json fail {$error}");
        }

        return $value;
    }

    private function asJson($value)
    {
        return json_encode($value, 256);
    }

    private function fromJson($value, $assoc = false)
    {
        return json_decode($value, $assoc);
    }

    private function asDate($value)
    {
        return date("Y-m-d", $value);
    }

    private function asDateTime($value)
    {
        return date("Y-m-d H:i:s", $value);
    }

    private function asTimestamp($value)
    {
        return strtotime($value);
    }

    /**
     * @return bool
     */
    public function isResetQuery(): bool
    {
        return $this->resetQuery;
    }

    /**
     * @param bool $resetQuery
     */
    public function setResetQuery(bool $resetQuery): void
    {
        $this->resetQuery = $resetQuery;
    }

    /**
     * @return bool
     */
    public function isToArrayNotNull(): bool
    {
        return $this->toArrayNotNull;
    }

    /**
     * @param bool $toArrayNotNull
     */
    public function setToArrayNotNull(bool $toArrayNotNull): void
    {
        $this->toArrayNotNull = $toArrayNotNull;
    }

    /**
     * @return bool
     */
    public function isToArrayStrict(): bool
    {
        return $this->toArrayStrict;
    }

    /**
     * @param bool $toArrayStrict
     */
    public function setToArrayStrict(bool $toArrayStrict): void
    {
        $this->toArrayStrict = $toArrayStrict;
    }
}
