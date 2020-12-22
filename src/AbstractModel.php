<?php


namespace EasySwoole\ORM;

use ArrayAccess;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Collection\Collection;
use EasySwoole\ORM\Db\ClientInterface;
use EasySwoole\ORM\Db\Cursor;
use EasySwoole\ORM\Db\CursorInterface;
use EasySwoole\ORM\Db\Result;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Utility\PreProcess;
use EasySwoole\ORM\Utility\TimeStampHandle;
use JsonSerializable;

/**
 * 抽象模型
 * Class AbstractMode
 * @package EasySwoole\ORM
 */
abstract class AbstractModel implements ArrayAccess, JsonSerializable
{
    use Concern\TimeStamp;
    use Concern\RelationShip;
    use Concern\Attribute;
    use Concern\Event;

    /** @var Result */
    private $lastQueryResult;
    private $lastQuery;


    protected $tableName;
    protected $tempTableName;

    /**
     * AbstractModel constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        $this->data($data);
    }



    /*  ==============    快速支持连贯操作    ==================   */

    /**
     * @param mixed ...$args
     * @return $this
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
     * @param int $page
     * @param int $limit
     * @return $this
     */
    public function page(int $page,int $limit = 10)
    {
        $this->limit(($page - 1) * $limit , $limit);
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
     * toArray时 隐藏字段
     * @param array|string $fields
     * @return $this
     */
    public function hidden($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $this->hidden = $fields;
        return $this;
    }

    /**
     * toArray时 追加显示的字段
     * @param array|string $append
     * @return $this
     */
    public function append($append)
    {
        if (!is_array($append)) {
            $append = [$append];
        }
        $this->append = $append;
        return $this;
    }

    /**
     * toArray时 规定要显示的字段
     * @param array|string $visible
     * @return $this
     */
    public function visible($visible)
    {
        if (!is_array($visible)) {
            $visible = [$visible];
        }
        $this->visible = $visible;
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
     * @param mixed ...$where
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

    /**
     * 别名设置
     * @param $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 预查询
     * @param $with
     * @param bool $supplyPk 设置了fields  但fields中不包含需要的主键，则自动补充
     * @return $this
     */
    public function with($with, $supplyPk = true){
        if (is_string($with)){
            $this->with = explode(',', $with);
        } else if (is_array($with)){
            $this->with = $with;
        }
        $this->supplyPk = $supplyPk;
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
     * 设置表名(一般用于分表)
     * @param string|null $name
     * @param bool $is_temp
     * @return string|$this
     * @throws Exception
     */
    public function tableName(?string $name = null, bool $is_temp = false)
    {
        if($name == null){
            return $this->tableName;
        }
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
     * @return int
     * @throws Exception
     * @throws \Throwable
     */
    public function count($field = null)
    {
        return (int)$this->queryPolymerization('count', $field);
    }

    /**
     * @param $field
     * @return mixed|null
     * @throws Exception
     * @throws \Throwable
     */
    public function avg($field)
    {
        return $this->queryPolymerization('avg', $field);
    }

    /**
     * @param $field
     * @return mixed|null
     * @throws Exception
     * @throws \Throwable
     */
    public function sum($field)
    {
        return $this->queryPolymerization('sum', $field);
    }

    /*  ==============    Builder 和 Result    ==================   */

    /**
     * @return Result|null
     */
    public function lastQueryResult(): ?Result
    {
        return $this->lastQueryResult;
    }

    /**
     * @return QueryBuilder|null
     */
    public function lastQuery(): ?QueryBuilder
    {
        return $this->lastQuery;
    }

    /**
     * duplicate
     * @param array $data
     * @return $this
     */
    public function duplicate(array $data)
    {
        $this->duplicate = $data;
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

        if (is_null($where) && $allow == false) {
            $this->preSetWhereFromExistModel($builder);
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
     * @throws Exception
     * @throws \Throwable
     * @return bool|int
     */
    public function save()
    {
        $builder = new QueryBuilder();
        $primaryKey = $this->schemaInfo()->getPkFiledName();
        if (empty($primaryKey)) {
            throw new Exception('save() needs primaryKey for model ' . static::class);
        }

        // beforeInsert事件
        $beforeRes = $this->callEvent('onBeforeInsert');
        if ($beforeRes === false){
            $this->callEvent('onAfterInsert', false);
            return false;
        }

        $rawArray = $this->data;
        // 合并时间戳字段
        $rawArray = TimeStampHandle::preHandleTimeStamp($this, $rawArray, 'insert');

        if ($this->duplicate) {
            $builder->onDuplicate($this->duplicate);
        }

        $builder->insert($this->getTableName(), $rawArray);
        $this->preHandleQueryBuilder($builder);

        $this->query($builder);
        if ($this->lastQueryResult()->getResult() === false) {
            $this->callEvent('onAfterInsert', false);
            return false;
        }

        $this->callEvent('onAfterInsert', true);
        if ($this->lastQueryResult()->getLastInsertId()) {
            // 自增id
            $autoIncrementField = $this->schemaInfo()->getAutoIncrementFiledName();
            if ($autoIncrementField){
                $this->data[$autoIncrementField] = $this->lastQueryResult()->getLastInsertId();
            }

            $this->originData = $this->data;
            return $this->lastQueryResult()->getLastInsertId();
        }
        return true;
    }

    /**
     * @param $data
     * @param bool $replace
     * @param bool $transaction 是否开启事务
     * @return array
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function saveAll($data, $replace = true, $transaction = true)
    {
        $pk = $this->schemaInfo()->getPkFiledName();
        if (empty($pk)) {
            throw new Exception('saveAll() needs primaryKey for model ' . static::class);
        }

        $connectionName = $this->getQueryConnection();

        // 开启事务
        if ($transaction){
            DbManager::getInstance()->startTransaction($connectionName);
        }

        $result = [];
        try{
            foreach ($data as $key => $row){
                // 如果有设置更新
                if ($replace && isset($row[$pk])){
                    $model = $this->_clone()->get($row[$pk]);
                    unset($row[$pk]);
                    $model->update($row);
                    $result[$key] = $model;
                }else{
                    $model = $this->_clone()->data($row);
                    $model->save();
                    $result[$key] = $model;
                }
            }
            if($transaction){
                DbManager::getInstance()->commit($connectionName);
            }
            return $result;
        } catch (\Throwable $e) {
            if($transaction) {
                DbManager::getInstance()->rollback($connectionName);
            }
            throw $e;
        }

    }

    /**
     * 获取数据
     * @param null $where
     * @return $this|null|array|bool|CursorInterface|Cursor
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function get($where = null)
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

        if ($res instanceof CursorInterface){
            $res->setModelName(static::class);
            return $res;
        }

        $model = $this->_clone();
        $model->data($res[0], false);
        $model->lastQuery = $this->lastQuery();
        $model->lastQueryResult = $this->lastQueryResult();

        // 预查询
        if (!empty($this->with)){
            $model->with($this->with);
            $model = $model->preHandleWith($model);
            $this->with = [];
        }
        return $model;
    }


    /**
     * 批量查询
     * @param null $where
     * @return array|bool|Cursor|CursorInterface|Collection
     * @throws Exception
     * @throws \Throwable
     */
    public function all($where = null)
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
        if ($results instanceof CursorInterface){
            $results->setModelName(static::class);
            return $results;
        }
        if (is_array($results)) {
            foreach ($results as $result) {
                $tem = $this->_clone()->data($result, false);
                $resultSet[] = $tem;
            }
            if (!empty($this->with)){
                $resultSet = $this->preHandleWith($resultSet);
                $this->with = [];
            }
        }
        if (DbManager::getInstance()->getConnection($this->connectionName)->getConfig()->isReturnCollection()){
            return new Collection($resultSet);
        }

        return $resultSet;
    }

    /**
     * @param string|null $column
     * @return array|null
     * @throws Exception
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
     * @param string|null $column
     * @return mixed|null
     * @throws Exception
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
        $data = $this->get();
        if (!$data) return $data;

        return  $data->getAttr($column);
    }

    /**
     * 更新
     * @param array $data
     * @param null $where
     * @param bool $allowUpdateWithNoCondition 是否允许无条件更新
     * @return bool
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function update(array $data = [], $where = null, $allowUpdateWithNoCondition = false)
    {
        if (!empty($data)) {
            foreach ($data as $columnKey => $columnValue){
                $this->setAttr($columnKey, $columnValue);
            }
        }

        $attachData = [];
        
        // 通过字段名去数据库更新
        foreach ($this->data as $tem_key => $tem_data){
            if (is_array($tem_data) && isset($tem_data["[I]"]) ){
                $attachData[$tem_key] = $tem_data;
                unset($this->data[$tem_key]);
            }
        }

        if (is_null($this->originData)) {
            $this->originData = [];
        }

        // 到此逻辑代码 已经走了修改器 $this->data
        if (($this->where || $where) && empty($data)) {
            // a情况 $where存在 $data不存在
            // data不存在 需要从$this->>data取数据 因为$where存在 不需要比较$this->originData
            $data = $this->data;
        } else if (($this->where || $where) && $data) {
            // b情况 $where存在 $data存在
            // $where存在 $data也存在 所以需要用key获取经过修改器的交集数据
            $data = array_intersect_key($this->data, $data);
        } else if (!($this->where || $where) && empty($data)) {
            // c情况 $where不存在 $data不存在
            // $where不存在 $data也不存在
            $data = array_diff_assoc($this->data, $this->originData);
        } else if (!($this->where || $where) && $data) {
            // d情况 $where不存在 $data存在
            // $where不存在 $data存在 需要用key获取经过修改器的交集数据 再进行$this->originData的差集
            $data = array_diff_assoc(array_intersect_key($this->data, $data), $this->originData);
        }

        $data = array_merge($data, $attachData);

        if (empty($data)) {
            $this->originData = $this->data;
            $this->callEvent('onBeforeUpdate', null);
            $this->callEvent('onAfterUpdate', true);
            return true;
        }

        $builder = new QueryBuilder();
        if ($where) {
            PreProcess::mappingWhere($builder, $where, $this);
        } else if (!$allowUpdateWithNoCondition) {
            $this->preSetWhereFromExistModel($builder);
        }
        $this->preHandleQueryBuilder($builder);
        // 合并时间戳字段
        $data = TimeStampHandle::preHandleTimeStamp($this, $data, 'update');
        $builder->update($this->getTableName(), $data);

        // beforeUpdate事件
        $beforeRes = $this->callEvent('onBeforeUpdate', $data);
        if ($beforeRes === false){
            $this->callEvent('onAfterUpdate', false);
            return false;
        }

        $results = $this->query($builder);
        if ($results) {
            foreach ($data as $key => $val) {
                if ((is_array($val) && isset($val["[I]"])) && isset($this->originData[$key])) {
                    $this->data[$key] = $this->originData[$key] + $val["[I]"];
                }
            }
            $this->originData = $this->data;
            $this->callEvent('onAfterUpdate', true);
        } else {
            $this->callEvent('onAfterUpdate', false);
        }

        return $results ? true : false;
    }

    /**
     * @param callable $call
     * @param int $size
     * @param int $chunkIndex
     * @return mixed|null
     * @throws Exception
     * @throws \Throwable
     */
    function chunk(callable $call,int $size = 10,int $chunkIndex = 1)
    {
        $this->resetQuery = false;
        try {
            $list = $this->page($chunkIndex,$size)->all();
            if (empty($list)) {
                return null;
            }

            foreach ($list as $value){
                call_user_func($call,$value);
            }
            $chunkIndex++;

            return $this->chunk($call,$size,$chunkIndex);
        }catch (\Throwable $throwable){
            throw $throwable;
        } finally {
            $this->resetQuery = true;
        }
    }



    // ================ Model内部底层支持开始  ======================

    /**
     * 实例化Model
     * @param array $data
     * @return AbstractModel|$this
     * @throws Exception
     */
    public static function create(array $data = []): AbstractModel
    {
        return new static($data);
    }

    /**
     * 继承式创建
     * @return AbstractModel|$this
     * @throws Exception
     */
    public function _clone(): AbstractModel
    {
        $model = new static();
        if ($this->connectionName !== $model->connectionName){
            $model->connection($this->connectionName);
        }
        if ($this->tableName !== $model->tableName){
            $model->tableName($this->tableName);
        }
        if (isset($this->client)){
            $model->setExecClient($this->client);
        }

        return $model;
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
     * 排他锁
     * @return $this
     */
    public function lockForUpdate()
    {
        $this->lock('FOR UPDATE');
        return $this;
    }

    /**
     * 共享锁
     * @return $this
     */
    public function sharedLock()
    {
        $this->lock('LOCK IN SHARE MODE');
        return $this;
    }


    private function lock(string $value)
    {
        $this->lock = $value;
    }

    /**
     * 类属性(连贯操作数据)清除
     */
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
        $this->hidden = [];
        $this->append = [];
        $this->visible = [];
        $this->lock = false;
        $this->duplicate = [];
    }

    /**
     * 执行QueryBuilder
     * @param QueryBuilder $builder
     * @param bool $raw
     * @return mixed
     * @throws \Throwable
     */
    public function query(QueryBuilder $builder, bool $raw = false)
    {
        $start = microtime(true);
        $this->lastQuery = clone $builder;
        $connection = $this->getQueryConnection();
        try {
            $ret = DbManager::getInstance()->query($builder, $raw, $connection);

            $builder->reset();

            $this->lastQueryResult = $ret;
            return $ret->getResult();
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            // 是否清除where条件
            if ($this->resetQuery) {
                $this->reset();
            }

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
            foreach ($this->where as $whereOne){
                if (is_array($whereOne[0]) || is_int($whereOne[0]) || is_callable($whereOne[0])){
                    $builder = PreProcess::mappingWhere($builder, $whereOne[0], $this);
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

        // 设置了lock
        if ($this->lock !== false) {
            $builder->setQueryOption($this->lock);
        }

        // 设置了with预查询 并且设置了fields  但fields中不包含需要的主键，则自动补充
        if (!empty($this->with) && $this->fields !== '*'){
            $this->preHandleWith = true;
            foreach ($this->with as $with => $params){

                if (is_numeric($with)) {
                    $withFuncResult = call_user_func([$this, $params]);
                }else{
                    $withFuncResult = call_user_func([$this, $with], $params);
                }

                $pk = $withFuncResult[2];
                if (!in_array($pk, $this->fields) && $this->supplyPk == true){
                    $this->fields[] = $pk;
                }
            }
            $this->preHandleWith = false;
        }

    }

    /**
     * 快捷查询 统一执行
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
                if(!is_numeric($field)){
                    $field = "`{$field}`";
                }
            }
        }

        $fields = "$type({$field})";
        $this->fields = $fields;
        $this->limit = 1;
        $res = $this->all();
        if (isset($res[0]->$fields)){
            return $res[0]->$fields;
        }

        return null;
    }

    /**
     * 取出链接
     * @param float|NULL $timeout
     * @return ClientInterface|null
     */
    public static function defer(float $timeout = null)
    {

        $model = new static();
        $connectionName = $model->getConnectionName();

        return DbManager::getInstance()->getConnection($connectionName)->defer($timeout);
    }

    /**
     * 闭包注入QueryBuilder执行
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

    /**
     * invoke执行Model
     * @param ClientInterface|Client $client
     * @param array $data
     * @return AbstractModel|$this
     * @throws Exception
     */
    public static function invoke(ClientInterface $client,array $data = []): AbstractModel
    {
        return (static::create($data))->setExecClient($client)->connection($client->connectionName());
    }

    /**
     * 从已经存在值的模型(get 或者 all 或者自己手动传递条件的) 预设回where条件
     * @param QueryBuilder $builder
     * @throws Exception
     */
    private function preSetWhereFromExistModel(QueryBuilder $builder)
    {
        try {
            $primaryKey = $this->schemaInfo()->getPkFiledName();

            if (empty($primaryKey)) {
                throw new Exception("Table not have primary key, so can\'t use Model::destroy(pk) or Model::update(pk)");
            }

            // tip: $this->originData[$primaryKeyOne] ?? $this->data[$primaryKeyOne] ?? null
            // 正常情况下pk不应该更新，所以从originData取
            // 用户不从get或者all获取model ，而是create(['pk'=> xx]) 所以也要从data取
            // 两个值都取不到 那就是空
            if (is_array($primaryKey)){// 复合主键
                foreach ($primaryKey as $primaryKeyOne){
                    $whereVal = $this->originData[$primaryKeyOne] ?? $this->data[$primaryKeyOne] ?? null;
                    if (empty($whereVal)){
                        throw new Exception("reunite table's primary key [{$primaryKeyOne}] value is empty, can't destroy or update");
                    }
                    $builder->where($primaryKeyOne, $whereVal);
                }
            }else{
                $whereVal = $this->originData[$primaryKey] ?? $this->data[$primaryKey] ?? null;
                if (empty($whereVal)) {
                    throw new Exception("table's primary key [{$primaryKey}] value is empty, can't destroy or update");
                }
                $builder->where($primaryKey, $whereVal);
            }
        } catch (Exception $e) {
            // 有任何异常抛出，则判断是否有设置了where条件
            if (empty($this->where)) throw $e;
        }
    }

}
