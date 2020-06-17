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

    /**@var ClientInterface */
    private $client;

    protected $tableName;
    protected $tempTableName;

    /**
     * AbstractModel constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data($data);
    }

    /**
     * 设置执行client
     * @param ClientInterface|null $client
     * @return $this
     */
    public function setExecClient(?ClientInterface $client)
    {
        $this->client = $client;
        return $this;
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
     * @param array $append
     * @return $this
     */
    public function append(array $append)
    {
        if (!is_array($append)) {
            $append = [$append];
        }
        $this->append = $append;
        return $this;
    }

    /**
     * toArray时 规定要显示的字段
     * @param array $visible
     * @return $this
     */
    public function visible(array $visible)
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
     * @return $this
     */
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
     * 设置表名(一般用于分表)
     * @param string $name
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
        $rawArray = $this->data;
        // 合并时间戳字段
        $rawArray = TimeStampHandle::preHandleTimeStamp($this, $rawArray, 'insert');
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

        if ($this->tempConnectionName) {
            $connectionName = $this->tempConnectionName;
        } else {
            $connectionName = $this->connectionName;
        }

        // 开启事务
        if ($transaction){
            DbManager::getInstance()->startTransaction($connectionName);
        }

        $result = [];
        try{
            foreach ($data as $key => $row){
                // 如果有设置更新
                if ($replace && isset($row[$pk])){
                    $model = static::create()->connection($connectionName)->get($row[$pk]);
                    unset($row[$pk]);
                    $model->update($row);
                    $result[$key] = $model;
                }else{
                    $model = static::create($row)->connection($connectionName);
                    $model->save();
                    $result[$key] = $model;
                }
            }
            if($transaction){
                DbManager::getInstance()->commit($connectionName);
            }
            return $result;
        } catch (\EasySwoole\Mysqli\Exception\Exception $e) {
            if($transaction) {
                DbManager::getInstance()->rollback($connectionName);
            }
            throw $e;
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
     * @return $this|null|array|bool
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

        $model = new static();
        $model->data($res[0], false);
        $model->lastQuery = $model->lastQuery();
        if ($this->client){
            $model->setExecClient($this->client);
        }
        // 预查询
        if (!empty($this->with)){
            $model->with($this->with);
            $model = $model->preHandleWith($model);
        }
        return $model;
    }


    /**
     * 批量查询
     * @param null $where
     * @return array|bool|Cursor|Collection
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
                $tem = (new static)->connection($this->connectionName)->data($result, false);
                if ($this->client){
                    $tem->setExecClient($this->client);
                }
                $resultSet[] = $tem;
            }
            if (!empty($this->with)){
                $resultSet = $this->preHandleWith($resultSet);
            }
        }
        if (DbManager::getInstance()->getConnection($this->connectionName)->getConfig()->isReturnCollection()){
            return new Collection($resultSet);
        }

        return $resultSet;
    }

    /**
     * @param string $column
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
     * @param string $column
     * @return mixed
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
     * @param bool $allow 是否允许无条件更新
     * @return bool
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \Throwable
     */
    public function update(array $data = [], $where = null, $allow = false)
    {
        if (!empty($data)) {
            foreach ($data as $columnKey => $columnValue){
                $this->setAttr($columnKey, $columnValue);
            }
        }

        $attachData = [];
        // 遍历属性，把inc 和dec 的属性先处理
        // 能进入这里，证明在setter预算不了，只能通过字段名去数据库更新
        foreach ($this->data as $tem_key => $tem_data){
            if (is_array($tem_data) && isset($tem_data["[I]"]) ){
                $attachData[$tem_key] = $tem_data;
                unset($this->data[$tem_key]);
            }
        }

        if (is_null($this->originData)) {
            $this->originData = [];
        }

        $data = array_diff_assoc($this->data, $this->originData);
        $data = array_merge($data, $attachData);

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
        $data = TimeStampHandle::preHandleTimeStamp($this, $data, 'update');
        $builder->update($this->getTableName(), $data);

        // beforeUpdate事件
        $beforeRes = $this->callEvent('onBeforeUpdate', $data);
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

    function chunk(callable $call,int $size = 10,int $chunkIndex = 1)
    {
        $list = $this->page($chunkIndex,$size)->all();
        if(!empty($list)){
            foreach ($list as $value){
                call_user_func($call,$value);
            }
            $chunkIndex++;
            return $this->chunk($call,$size,$chunkIndex);
        }else{
            return null;
        }
    }



    // ================ Model内部底层支持开始  ======================

    /**
     * 实例化Model
     * @param array $data
     * @return AbstractModel|$this
     */
    public static function create(array $data = []): AbstractModel
    {
        return new static($data);
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
    public function preHandleQueryBuilder(QueryBuilder $builder)
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
        try {
            $model = new static();
        } catch (Exception $e) {
            return null;
        }
        $connectionName = $model->connectionName;

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
        return (static::create($data))->setExecClient($client);
    }

    /**
     * 获取invoke注入的客户端
     * @return ClientInterface|null
     */
    public function getExecClient()
    {
        if ($this->client){
            return $this->client;
        }
        return null;
    }

}
