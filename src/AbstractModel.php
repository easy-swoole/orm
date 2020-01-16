<?php


namespace EasySwoole\ORM;

use ArrayAccess;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\ClientInterface;
use EasySwoole\ORM\Db\Result;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Utility\PreProcess;
use EasySwoole\ORM\Utility\Schema\Table;
use EasySwoole\ORM\Utility\TableObjectGeneration;
use JsonSerializable;

/**
 * 抽象模型
 * Class AbstractMode
 * @package EasySwoole\ORM
 */
abstract class AbstractModel implements ArrayAccess, JsonSerializable
{
    /** @var Result */
    private $lastQueryResult;
    private $lastQuery;
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
    /** @var Table */
    private static $schemaInfoList;
    /**
     * 当前连接驱动类的名称
     * 继承后可以覆盖该成员以指定默认的驱动类
     * @var string
     */
    protected $connectionName = 'default';
    /*
     * 临时设定的链接
     */
    private $tempConnectionName = null;
    /**
     * 当前的数据
     * @var array
     */
    private $data = [];
    /**
     * 附加数据
     * @var array
     */
    private $_joinData = [];
    /**
     * 模型的原始数据
     * 未应用修改器和获取器之前的原始数据
     * @var array
     */
    private $originData;
    /* 回调事件 */
    private $onQuery;
    /** @var string 临时表名 */
    private $tempTableName = null;
    /**
     * @var ClientInterface
     */
    private $client;

    /** @var bool|string 是否开启时间戳 */
    protected  $autoTimeStamp = false;
    /** @var bool|string 创建时间字段名 false不设置 */
    protected  $createTime = 'create_time';
    /** @var bool|string 更新时间字段名 false不设置 */
    protected  $updateTime = 'update_time';
    /** @var array 预查询 */
    private $with;
    /** @var bool 是否为预查询 */
    private $preHandleWith = false;

    /**
     * AbstractModel constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        $this->data($data);
    }

    public function setExecClient(?ClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }


    /**
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


    /*  ==============    回调事件    ==================   */
    public function onQuery(callable $call)
    {
        $this->onQuery = $call;
        return $this;
    }

    /**
     * 调用事件
     * @param $eventName
     * @param array $param
     * @return bool|mixed
     */
    protected function callEvent($eventName, ...$param)
    {
        if(method_exists(static::class, $eventName)){
            return call_user_func([static::class, $eventName], $this, ...$param);
        }
        return true;
    }

    /*  ==============    快速支持连贯操作    ==================   */
    /**
     * @param mixed ...$args
     * @return AbstractModel
     */
    public function order(...$args)
    {
        $this->order[] = $args;
        return $this;
    }
    /**
     * @param int $one
     * @param int|null $two
     * @return $this
     */
    public function limit(int $one, ?int $two = null)
    {
        if ($two !== null) {
            $this->limit = [$one, $two];
        } else {
            $this->limit = $one;
        }
        return $this;
    }
    /**
     * @param $fields
     * @return $this
     */
    public function field($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $this->fields = $fields;
        return $this;
    }
    /**
     * @return $this
     */
    public function withTotalCount()
    {
        $this->withTotalCount = true;
        return $this;
    }
    /**
     * @param $where
     * @return $this
     */
    public function where(...$where)
    {
        $this->where[] = $where;
        return $this;
    }
    /**
     * @param string $group
     * @return $this
     */
    public function group(string $group)
    {
        $this->group = $group;
        return $this;
    }
    /**
     * @param $joinTable
     * @param $joinCondition
     * @param string $joinType
     * @return $this
     */
    public function join($joinTable, $joinCondition, $joinType = '')
    {
        $this->join[] = [$joinTable, $joinCondition, $joinType];
        return $this;
    }

    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function with($with){
        if (is_string($with)){
            $this->with = explode(',', $with);
        } else if (is_array($with)){
            $this->with = $with;
        }
        return $this;
    }

    /**
     * 获取表名，如果有设置临时表名则返回临时表名
     * @throws
     */
    public function getTableName()
    {
        if($this->tempTableName !== null){
            return $this->tempTableName;
        }else{
           return $this->schemaInfo()->getTable();
        }
    }

    /**
     * @param string $name
     * @param bool $is_temp
     * @return $this
     * @throws Exception
     */
    public function tableName(string $name, bool $is_temp = false)
    {
        if ($is_temp){
            $this->tempTableName = $name;
        }else{
            if($name != $this->tableName){
                $this->tableName = $name;
                $this->schemaInfo(false);
            }
        }
        return $this;
    }

    private function parseTableName()
    {
        $table = $this->getTableName();
        if ($this->alias !== NULL){
            $table .= " AS `{$this->alias}`";
        }
        return $table;
    }

    /*  ==============    聚合查询    ==================   */

    /**
     * @param $field
     * @return null
     * @throws Exception
     * @throws \Throwable
     */
    public function max($field)
    {
        return $this->queryPolymerization('max', $field);
    }

    /**
     * @param $field
     * @return null
     * @throws Exception
     * @throws \Throwable
     */
    public function min($field)
    {
        return $this->queryPolymerization('min', $field);
    }

    /**
     * @param null $field
     * @return null
     * @throws Exception
     * @throws \Throwable
     */
    public function count($field = null)
    {
        return (int)$this->queryPolymerization('count', $field);
    }

    /**
     * @param $field
     * @return null
     * @throws Exception
     * @throws \Throwable
     */
    public function avg($field)
    {
        return $this->queryPolymerization('avg', $field);
    }

    /**
     * @param $field
     * @return null
     * @throws Exception
     * @throws \Throwable
     */
    public function sum($field)
    {
        return $this->queryPolymerization('sum', $field);
    }

    /*  ==============    Builder 和 Result    ==================   */
    public function lastQueryResult(): ?Result
    {
        return $this->lastQueryResult;
    }
    public function lastQuery(): ?QueryBuilder
    {
        return $this->lastQuery;
    }

    function connection(string $name, bool $isTemp = false): AbstractModel
    {
        if ($isTemp) {
            $this->tempConnectionName = $name;
        } else {
            $this->connectionName = $name;
        }
        return $this;
    }

    public function getAttr($attrName)
    {
        $method = 'get' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
        if (method_exists($this, $method)) {
            return $this->$method($this->data[$attrName] ?? null, $this->data);
        }
        // 判断是否有关联查询
        if (method_exists($this, $attrName)) {
            return $this->$attrName();
        }
        // 是否是附加字段
        if (isset($this->_joinData[$attrName])){
            return $this->_joinData[$attrName];
        }
        return $this->data[$attrName] ?? null;
    }

    /**
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
            $attrValue = PreProcess::dataValueFormat($attrValue, $col);
            $method = 'set' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
            if ($setter && method_exists($this, $method)) {
                $attrValue = $this->$method($attrValue, $this->data);
            }
            $this->data[$attrName] = $attrValue;
            return true;
        } else {
            $this->_joinData[$attrName] = $attrValue;
            return false;
        }
    }

    /**
     * 数据赋值
     * @param array $data
     * @param bool $setter 是否调用setter
     * @return $this
     * @throws Exception
     */
    public function data(array $data, $setter = true)
    {
        foreach ($data as $key => $value) {
            $this->setAttr($key, $value, $setter);
        }
        $this->originData = $this->data;
        return $this;
    }

    /**
     * @param null $where
     * @param bool $allow 是否允许没有主键删除
     * @return int|bool
     * @throws Exception
     * @throws \Throwable
     */
    public function destroy($where = null, $allow = false)
    {
        $builder = new QueryBuilder();
        $primaryKey = $this->schemaInfo()->getPkFiledName();

        if (is_null($where) && $allow == false) {
            if (empty($primaryKey)) {
                throw new Exception('Table not have primary key, so can\'t use Model::destroy($pk)');
            } else {
                $whereVal = $this->getAttr($primaryKey);
                if (empty($whereVal)) {
                    if (empty($this->where)){
                        throw new Exception('Table not have primary value');
                    }
                }else{
                    $builder->where($primaryKey, $whereVal);
                }
            }
        }

        PreProcess::mappingWhere($builder, $where, $this);
        $this->preHandleQueryBuilder($builder);
        $builder->delete($this->getTableName(), $this->limit);

        // beforeDelete事件
        $beforeRes = $this->callEvent('onBeforeDelete');
        if ($beforeRes === false){
            $this->callEvent('onAfterDelete', false);
            return false;
        }

        $this->query($builder);
        //  是否出错
        if ($this->lastQueryResult()->getResult() === false) {
            $this->callEvent('onAfterDelete', false);
            return false;
        }

        $this->callEvent('onAfterDelete', $this->lastQueryResult()->getAffectedRows());
        return $this->lastQueryResult()->getAffectedRows();
    }

    /**
     * 保存 插入
     * @param bool $notNul
     * @param bool $strict
     * @throws Exception
     * @throws \Throwable
     * @return bool|int
     */
    public function save($notNul = false, $strict = true)
    {
        $builder = new QueryBuilder();
        $primaryKey = $this->schemaInfo()->getPkFiledName();
        if (empty($primaryKey)) {
            throw new Exception('save() needs primaryKey for model ' . static::class);
        }
        $rawArray = $this->toArray($notNul, $strict);
        // 合并时间戳字段
        $rawArray = $this->preHandleTimeStamp($rawArray, 'insert');
        $builder->insert($this->getTableName(), $rawArray);
        $this->preHandleQueryBuilder($builder);
        // beforeInsert事件
        $beforeRes = $this->callEvent('onBeforeInsert');
        if ($beforeRes === false){
            $this->callEvent('onAfterInsert', false);
            return false;
        }

        $this->query($builder);
        if ($this->lastQueryResult()->getResult() === false) {
            $this->callEvent('onAfterInsert', false);
            return false;
        }

        $this->callEvent('onAfterInsert', true);
        if ($this->lastQueryResult()->getLastInsertId()) {
            $this->data[$primaryKey] = $this->lastQueryResult()->getLastInsertId();
            $this->originData = $this->data;
            return $this->lastQueryResult()->getLastInsertId();
        }
        return true;
    }

    /**
     * @param $data
     * @param bool $replace
     * @return array
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function saveAll($data, $replace = true)
    {
        $pk = $this->schemaInfo()->getPkFiledName();
        if (empty($pk)) {
            throw new Exception('saveAll() needs primaryKey for model ' . static::class);
        }

        // 开启事务
        DbManager::getInstance()->startTransaction($this->connectionName);
        $result = [];

        try{
            foreach ($data as $key => $row){
                // 如果有设置更新
                if ($replace && isset($row[$pk])){
                    $model = static::create()->connection($this->connectionName)->get($row[$pk]);
                    unset($row[$pk]);
                    $model->update($row);
                    $result[$key] = $model;
                }else{
                    $model = static::create($row)->connection($this->connectionName);
                    $res = $model->save();
                    $result[$key] = $model;
                }
            }
            DbManager::getInstance()->commit($this->connectionName);
            return $result;
        } catch (\EasySwoole\Mysqli\Exception\Exception $e) {
            DbManager::getInstance()->rollback($this->connectionName);
            throw $e;
        } catch (\Throwable $e) {
            DbManager::getInstance()->rollback($this->connectionName);
            throw $e;
        }

    }

    /**
     * 获取数据
     * @param null $where
     * @param bool $returnAsArray
     * @return $this|null|array|bool
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function get($where = null, bool $returnAsArray = false)
    {
        $builder = new QueryBuilder;
        $builder = PreProcess::mappingWhere($builder, $where, $this);
        $this->preHandleQueryBuilder($builder);
        $builder->getOne($this->parseTableName(), $this->fields);
        $res = $this->query($builder);

        if (empty($res)) {
            if ($res === false){
                return false;
            }
            return null;
        }
        if ($returnAsArray){
            return $res[0];
        }
        $this->data($res[0], false);
        $this->lastQuery = $this->lastQuery();
        // 预查询
        if (!empty($this->with)){
            $this->preHandleWith($this);
        }
        return $this;
    }


    /**
     * 批量查询
     * @param null $where
     * @param bool $returnAsArray
     * @return array|bool
     * @throws Exception
     * @throws \Throwable
     */
    public function all($where = null, bool $returnAsArray = false)
    {
        $builder = new QueryBuilder;
        $builder = PreProcess::mappingWhere($builder, $where, $this);
        $this->preHandleQueryBuilder($builder);
        $builder->get($this->parseTableName(), $this->limit, $this->fields);
        $results = $this->query($builder);
        $resultSet = [];
        if ($results === false){
            return false;
        }
        if (is_array($results)) {
            foreach ($results as $result) {
                if ($returnAsArray) {
                    $resultSet[] = $result;
                } else {
                    $resultSet[] = (new static)->connection($this->connectionName)->data($result, false);
                }
            }
            if (!$returnAsArray && !empty($this->with)){
                $resultSet = $this->preHandleWith($resultSet);
            }
        }
        return $resultSet;
    }

    /**
     * 批量查询 不映射对象  返回数组
     * @param null $where
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    public function select($where = null):array
    {
        return $this->all($where, true);
    }

    /**
     * @param null $where
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    public function findAll($where = null):array
    {
        return $this->select($where);
    }

    /**
     * @param null $where
     * @return array|AbstractModel|null
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function findOne($where = null)
    {
        return $this->get($where, true);
    }

    /**
     * @param string $column
     * @return array|null
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function column(?string $column = null): ?array
    {
        if (!is_null($column)) {
            $this->fields = [$column];
        }
        $this->all();

        return $this->lastQueryResult->getResultColumn($column);
    }

    /**
     * @param string $column
     * @return mixed
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function scalar(?string $column = null)
    {
        if (!is_null($column)) {
            $this->fields = [$column];
        }
        $this->limit = 1;
        $this->all();

        return $this->lastQueryResult->getResultScalar($column);
    }

    /**
     * @param string $column
     * @return array|null
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function indexBy(string $column): ?array
    {
        $this->all();

        return $this->lastQueryResult->getResultIndexBy($column);
    }

    /**
     * 直接返回某一行的某一列
     * @param $column
     * @return mixed|null
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function val($column)
    {
        $data = $this->findOne();
        return isset($data[$column]) ? $data[$column] : null;
    }

    /**
     * @param array $data
     * @return AbstractModel|$this
     * @throws Exception
     */
    public static function create(array $data = []): AbstractModel
    {
        return new static($data);
    }

    /**
     * @param ClientInterface $client
     * @param array $data
     * @return AbstractModel
     * @throws Exception
     */
    public static function invoke(ClientInterface $client,array $data = []): AbstractModel
    {
        return (static::create($data))->setExecClient($client);
    }


    /**
     * 更新
     * @param array $data
     * @param null $where
     * @param bool $allow 是否允许无条件更新
     * @return bool
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function update(array $data = [], $where = null, $allow = false)
    {
        if (empty($data)) {
            // $data = $this->toArray();
            $data = array_diff_assoc($this->data, $this->originData);
            if (empty($data)) {
                return true;
            }
        }else{
            foreach ($data as $columnKey => $columnValue){
                $this->setAttr($columnKey, $columnValue);
            }
            $data = array_diff_assoc($this->data, $this->originData);
        }

        if (empty($data)){
            $this->originData = $this->data;
            return true;
        }

        $builder = new QueryBuilder();
        if ($where) {
            PreProcess::mappingWhere($builder, $where, $this);
        } else if (!$allow) {
            $pk = $this->schemaInfo()->getPkFiledName();
            if (isset($this->data[$pk])) {
                $pkVal = $this->data[$pk];
                $builder->where($pk, $pkVal);
            } else {
                if (empty($this->where)){
                    throw new Exception("update error,pkValue is require");
                }
            }
        }
        $this->preHandleQueryBuilder($builder);
        // 合并时间戳字段
        $data = $this->preHandleTimeStamp($data, 'update');
        $builder->update($this->getTableName(), $data);

        // beforeUpdate事件
        $beforeRes = $this->callEvent('onBeforeUpdate');
        if ($beforeRes === false){
            $this->callEvent('onAfterUpdate', false);
            return false;
        }

        $results = $this->query($builder);
        if ($results){
            $this->originData = $this->data;
            $this->callEvent('onAfterUpdate', true);
        }else{
            $this->callEvent('onAfterUpdate', false);
        }

        return $results ? true : false;
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

    public function toArray($notNul = false, $strict = true): array
    {
        $temp = $this->data ?? [];
        if ($notNul) {
            foreach ($temp as $key => $value) {
                if ($value === null) {
                    unset($temp[$key]);
                }
            }
            if (!$strict) {
                $temp = array_merge($temp, $this->_joinData ?? []);
            }
            return $temp;
        }
        if (is_array($this->fields)) {
            foreach ($temp as $key => $value) {
                if (in_array($key, $this->fields)) {
                    unset($temp[$key]);
                }
            }
        }
        if (!$strict) {
            $temp = array_merge($temp, $this->_joinData ?? []);
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
        return ($this->getAttr($name) !== null);
    }

    /**
     * @param callable $call
     * @return mixed
     * @throws \Throwable
     */
    function func(callable $call)
    {
        $builder = new QueryBuilder();
        $isRaw = (bool)call_user_func($call,$builder);
        return $this->query($builder,$isRaw);
    }

    private function reset()
    {
        $this->tempConnectionName = null;
        $this->fields = "*";
        $this->limit  = null;
        $this->withTotalCount = false;
        $this->order  = null;
        $this->where  = [];
        $this->join   = null;
        $this->group  = null;
        $this->alias  = null;
        $this->tempTableName = null;
    }

    /**
     * @param string        $class
     * @param callable|null $where
     * @param null          $pk
     * @param null          $joinPk
     * @param string        $joinType
     * @return mixed|null
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected function hasOne(string $class, callable $where = null, $pk = null, $joinPk = null, $joinType = '')
    {
        if ($this->preHandleWith === true){
            return [$class, $where, $pk, $joinPk, $joinType, 'hasOne'];
        }

        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }

        $ref = new \ReflectionClass($class);

        if (!$ref->isSubclassOf(AbstractModel::class)) {
            throw new Exception("relation class must be subclass of AbstractModel");
        }

        /** @var AbstractModel $ins */
        $ins = $ref->newInstance();
        $builder = new QueryBuilder();

        if ($pk === null) {
            $pk = $this->schemaInfo()->getPkFiledName();
        }
        if ($joinPk === null) {
            $joinPk = $ins->schemaInfo()->getPkFiledName();
        }

        $targetTable = $ins->schemaInfo()->getTable();
        $currentTable = $this->schemaInfo()->getTable();

        // 支持复杂的构造
        if ($where) {
            /** @var QueryBuilder $builder */
            $builder = call_user_func($where, $builder);
            $this->preHandleQueryBuilder($builder);
            $builder->getOne($targetTable, $builder->getField());
        } else {
            $targetTableAlias = "ES_INS";
            // 关联表字段自动别名
            $fields = $this->parserRelationFields($this, $ins, $targetTableAlias);

            $builder->join($targetTable." AS {$targetTableAlias}", "{$targetTableAlias}.{$joinPk} = {$currentTable}.{$pk}", $joinType)
                ->where("{$currentTable}.{$pk}", $this->$pk);
            $this->preHandleQueryBuilder($builder);
            $builder->getOne($currentTable, $fields);
        }

        $result = $this->query($builder);
        if ($result) {
            // 分离结果 两个数组
            $targetData = [];
            $originData = [];
            foreach ($result[0] as $key => $value){
                if (isset($targetTableAlias)) {
                    // 如果有包含附属别名，则是targetData
                    if (strpos($key, $targetTableAlias) !==  false){
                        $trueKey = ltrim($key, $targetTableAlias."_");
                        $targetData[$trueKey] = $value;
                    }else{
                        $originData[$key] = $value;
                    }
                }else{
                    $targetData[$key] = $value;
                }
            }

            $this->data($originData, false);
            $ins->data($targetData, false);
            $this->_joinData[$fileName] = $ins;

            return $this->_joinData[$fileName];
        }
        return null;

    }

    /**
     * 一对多关联
     * @param string        $class
     * @param callable|null $where
     * @param null          $pk
     * @param null          $joinPk
     * @param string        $joinType
     * @return mixed|null
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected function hasMany(string $class, callable $where = null, $pk = null, $joinPk = null, $joinType = '')
    {
        if ($this->preHandleWith === true){
            return [$class, $where, $pk, $joinPk, $joinType, 'hasMany'];
        }

        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }

        $ref = new \ReflectionClass($class);

        if (!$ref->isSubclassOf(AbstractModel::class)) {
            throw new Exception("relation class must be subclass of AbstractModel");
        }

        /** @var AbstractModel $ins */
        $ins = $ref->newInstance();
        $builder = new QueryBuilder();

        if ($pk === null) {
            $pk = $this->schemaInfo()->getPkFiledName();
        }
        if ($joinPk === null) {
            $joinPk = $ins->schemaInfo()->getPkFiledName();
        }

        $targetTable = $ins->schemaInfo()->getTable();
        $currentTable = $this->schemaInfo()->getTable();

        // 支持复杂的构造
        if ($where) {
            /** @var QueryBuilder $builder */
            $builder = call_user_func($where, $builder);
            $this->preHandleQueryBuilder($builder);
            $builder->get($targetTable, null, $builder->getField());
        } else {
            $targetTableAlias = "ES_INS";
            // 关联表字段自动别名
            $fields = $this->parserRelationFields($this, $ins, $targetTableAlias);

            $builder->join($targetTable." AS {$targetTableAlias}", "{$targetTableAlias}.{$joinPk} = {$currentTable}.{$pk}", $joinType)
                ->where("{$currentTable}.{$pk}", $this->$pk);
            $this->preHandleQueryBuilder($builder);
            $builder->get($currentTable, null, $fields);
        }

        $result = $this->query($builder);
        if ($result) {
            $return = [];
            foreach ($result as $one) {
                // 分离结果 两个数组
                $targetData = [];
                $originData = [];
                foreach ($one as $key => $value){
                    if(isset($targetTableAlias)){
                        // 如果有包含附属别名，则是targetData
                        if (strpos($key, $targetTableAlias) !==  false){
                            $trueKey = ltrim($key, $targetTableAlias."_");
                            $targetData[$trueKey] = $value;
                        }else{
                            $originData[$key] = $value;
                        }
                    }else{
                        // callable $where 自行处理字段
                        $targetData[$key] = $value;
                    }
                }
                $return[] = ($ref->newInstance())->data($targetData);
            }
            $this->_joinData[$fileName] = $return;

            return $this->_joinData[$fileName];
        }
        return null;
    }

    /**
     * 关联查询 字段自动别名解析
     * @param AbstractModel $model
     * @param AbstractModel $ins
     * @param string $insAlias
     * @return array
     * @throws Exception
     */
    protected function parserRelationFields($model, $ins, $insAlias)
    {
        $currentTable = $model->schemaInfo()->getTable();
        $insFields = array_keys($ins->schemaInfo()->getColumns());
        $fields    = [];
        $fields[]  = "{$currentTable}.*";
        foreach ($insFields as $field){
            $fields[] = "{$insAlias}.{$field} AS {$insAlias}_{$field}";
        }
        return $fields;
    }

    /**
     * @param QueryBuilder $builder
     * @param bool $raw
     * @return mixed
     * @throws \Throwable
     */
    protected function query(QueryBuilder $builder, bool $raw = false)
    {
        $start = microtime(true);
        $this->lastQuery = clone $builder;
        if ($this->tempConnectionName) {
            $connectionName = $this->tempConnectionName;
        } else {
            $connectionName = $this->connectionName;
        }
        try {
            $ret = null;
            if($this->client){
                $ret = DbManager::getInstance()->query($builder, $raw, $this->client);
            }else{
                $ret = DbManager::getInstance()->query($builder, $raw, $connectionName);
            }
            $builder->reset();
            $this->lastQueryResult = $ret;
            return $ret->getResult();
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            $this->reset();
            if ($this->onQuery) {
                $temp = clone $builder;
                call_user_func($this->onQuery, $ret, $temp, $start);
            }
        }
    }

    /**
     * 连贯操作预处理
     * @param QueryBuilder $builder
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    private function preHandleQueryBuilder(QueryBuilder $builder)
    {
        // 快速连贯操作
        if ($this->withTotalCount) {
            $builder->withTotalCount();
        }
        if ($this->order && is_array($this->order)) {
            foreach ($this->order as $order){
                $builder->orderBy(...$order);
            }
        }
        if ($this->where) {
            $whereModel = new static();
            foreach ($this->where as $whereOne){
                if (is_array($whereOne[0]) || is_int($whereOne[0])){
                    $builder = PreProcess::mappingWhere($builder, $whereOne[0], $whereModel);
                }else{
                    $builder->where(...$whereOne);
                }
            }
        }
        if($this->group){
            $builder->groupBy($this->group);
        }
        if($this->join){
            foreach ($this->join as $joinOne) {
                $builder->join($joinOne[0], $joinOne[1], $joinOne[2]);
            }
        }
        // 如果在闭包里设置了属性，并且Model没设置，则覆盖Model里的
        if ( $this->fields == '*' ){
            $this->fields = implode(', ', $builder->getField());
        }

    }

    /**
     * @param $type
     * @param null $field
     * @return null|mixed
     * @throws Exception
     * @throws \Throwable
     */
    private function queryPolymerization($type, $field = null)
    {
        if ($field === null){
            $field = $this->schemaInfo()->getPkFiledName();
        }
        // 判断字段中是否带了表名，是否有`
        if (strstr($field, '`') == false){
            // 有表名
            if (strstr($field, '.') !== false){
                $temArray = explode(".", $field);
                $field = "`{$temArray[0]}`.`{$temArray[1]}`";
            }else{
                $field = "`{$field}`";
            }
        }

        $fields = "$type({$field})";
        $this->fields = $fields;
        $this->limit = 1;
        $res = $this->all(null, true);

        if (isset($res[0][$fields])){
            return $res[0][$fields];
        }

        return null;
    }

    /**
     * 处理时间戳
     * @param $data
     * @param string $doType
     * @return mixed
     * @throws Exception
     */
    private function preHandleTimeStamp($data, $doType = 'insert')
    {
        if ($this->autoTimeStamp === false){
            return $data;
        }
        $type = 'int';

        if ( $this->autoTimeStamp === 'datetime'){
            $type = 'datetime';
        }

        switch ($doType){
            case 'insert':
                if ($this->createTime !== false){
                    $tem = $this->parseTimeStamp(time(), $type);
                    $this->setAttr($this->createTime, $tem);
                    $data[$this->createTime] = $tem;
                }
                if ($this->updateTime !== false){
                    $tem = $this->parseTimeStamp(time(), $type);
                    $this->setAttr($this->updateTime, $tem);
                    $data[$this->updateTime] = $tem;
                }
                break;
            case 'update':
                if ($this->updateTime !== false){
                    $tem = $this->parseTimeStamp(time(), $type);
                    $this->setAttr($this->updateTime, $tem);
                    $data[$this->updateTime] = $tem;
                }
                break;
        }

        return $data;
    }

    private function parseTimeStamp(int $timestamp, $type = 'int')
    {
        switch ($type){
            case 'int':
                return $timestamp;
                break;
            case 'datetime':
                return date('Y-m-d H:i:s', $timestamp);
                break;
            default:
                return date($type, $timestamp);
                break;
        }
    }

    // ================ 关联预查询  ======================
    private function preHandleWith($data)
    {
        // $data 只有一条 直接foreach调用 $data->$with();
        if ($data instanceof AbstractModel){// get查询使用
            foreach ($this->with as $with){
                $data->$with();
            }
            return $data;
        }else if (is_array($data) && !empty($data)){// all查询使用
            // $data 是多条，需要先提取主键数组，select 副表 where joinPk in (pk arrays);
            // foreach 判断主键，设置值
            foreach ($this->with as $with){
                list($class, $where, $pk, $joinPk, $joinType, $withType) = $data[0]->$with();
                if ($pk !== null && $joinPk !== null){
                    $data[0]->preHandleWith = true;
                    $pks = array_map(function ($v) use ($pk){
                        return $v->$pk;
                    }, $data);
                    /** @var AbstractModel $insClass */
                    $insClass = new $class;
                    $insData  = $insClass->where($joinPk, $pks, 'IN')->all();
                    $temData  = [];
                    foreach ($insData as $insK => $insV){
                        if ($withType=='hasOne'){
                            $temData[$insV[$pk]] = $insV;
                        }else if($withType=='hasMany'){
                            $temData[$insV[$pk]][] = $insV;
                        }
                    }
                    foreach ($data as $model){
                        if (isset($temData[$model[$pk]])){
                            $model[$with] = $temData[$model[$pk]];
                        }
                    }
                    $data[0]->preHandleWith = false;
                } else {
                    // 闭包的只能一个一个调用
                    foreach ($data as $model){
                        foreach ($this->with as $with){
                            $model->$with();
                        }
                    }
                }
            }
            return $data;
        }
        return $data;
    }
}
