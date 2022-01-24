<?php

namespace EasySwoole\ORM;

use EasySwoole\DDL\Blueprint\Create\Column;
use EasySwoole\DDL\Blueprint\Create\Table;
use EasySwoole\DDL\Enum\DataType;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\QueryResult;
use EasySwoole\ORM\Exception\ExecuteFail;
use EasySwoole\ORM\Exception\ModelError;


abstract class AbstractModel implements \ArrayAccess , \JsonSerializable
{
    /** @var RuntimeConfig */
    private $runtimeConfig;
    /** @var null|array */
    private $__data = null;
    /** @var QueryResult|null */
    private $__lastQueryResult;

    private $__preQueryData = [];

    abstract function tableName():string;

    function __construct(?array $data = null)
    {
        if(!empty($data)){
            $this->data($data);
        }
    }

    public static function create(?array $data = null):AbstractModel
    {
        return new static($data);
    }

    public function data(array $data, $setter = true)
    {
        foreach ($data as $key => $value) {
            $this->setAttr($key, $value, $setter);
        }
        return $this;
    }

    function runtimeConfig(?RuntimeConfig $config = null):RuntimeConfig
    {
        if($config == null){
            if($this->runtimeConfig == null){
                $this->runtimeConfig = new RuntimeConfig();
            }
        }else{
            $this->runtimeConfig = $config;
        }
        return $this->runtimeConfig;
    }

    function lastQueryResult():?QueryResult
    {
        return $this->__lastQueryResult;
    }

    function schemaInfo():Table
    {
        $key = $this->__modelhash();
        $item = RuntimeCache::getInstance()->get($key);
        if($item){
            return $item;
        }
        $client = $this->runtimeConfig()->getClient();
        $query = new QueryBuilder();
        $query->raw("show full columns from {$this->tableName()}");

        $fields = DbManager::getInstance()
            ->__exec($client,$query,false,$this->runtimeConfig()->getConnectionConfig()->getTimeout())
            ->getResult();
        $table = new Table($this->tableName());

        foreach ($fields as $field){
            //创建字段与类型处理
            $columnTypeArr = explode(' ',$field['Type']);
            $tmpIndex = strpos($columnTypeArr[0],'(');
            //例如  varchar(20)
            if($tmpIndex !== false){
                $type = substr($columnTypeArr[0],0,$tmpIndex);
                $limit = substr($columnTypeArr[0],$tmpIndex+1,strpos($columnTypeArr[0],')')-$tmpIndex-1);
                $columnObj = new Column($field['Field'],$type);
                $limitArr = explode(',',$limit);
                if (isset($limitArr[1])){
                    $columnObj->setColumnLimit($limitArr);
                }else{
                    $columnObj->setColumnLimit($limitArr[0]);
                }
            }else{
                $type = $columnTypeArr[0];
                $columnObj = new Column($field['Field'],$type);
            }
            if (in_array('unsigned',$columnTypeArr)){
                $columnObj->setIsUnsigned();
            }
            if ($field['Key']=='PRI'){
                $columnObj->setIsPrimaryKey();
            }
            //默认值
            if ($field['Default']!==null){
                $columnObj->setDefaultValue($field['Default']);
            }else{
                $columnObj->setDefaultValue(null);
            }
            if ($field['Extra']=='auto_increment'){
                $columnObj->setIsAutoIncrement();
            }
            if (!empty($field['Comment'])){
                $columnObj->setColumnComment($field['Comment']);
            }
            $table->addColumn($columnObj);
        }

        RuntimeCache::getInstance()->set($key,$table);

        return $table;
    }

    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getAttr($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->setAttr($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->setAttr($offset, null);
    }


    function __set($name, $value)
    {
        //访问的时候，恢复ddl定义的默认值
        if($this->__data === null){
            $this->__data = $this->__tableArray();
        }
        $this->setAttr($name, $value);
    }

    function __get($name)
    {
        //访问的时候，恢复ddl定义的默认值
        if($this->__data === null){
            $this->__data = $this->__tableArray();
        }
        return $this->getAttr($name);
    }

    public function __isset($name)
    {
        return ($this->getAttr($name) !== null);
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

        return $this->__data[$attrName] ?? null;
    }

    public function setAttr($attrName, $attrValue, $setter = true): bool
    {
        if (isset($this->schemaInfo()->getColumns()[$attrName])) {
            /** @var Column $col */
            $col = $this->schemaInfo()->getColumns()[$attrName];
            if(DataType::typeIsTextual($col->getColumnType())){
                $attrValue = strval($attrValue);
            }
            $method = 'set' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
            if ($setter && method_exists($this, $method)) {
                $attrValue = $this->$method($attrValue, $this->__data);
            }
            $this->__data[$attrName] = $attrValue;
            return true;
        } else {
            return false;
        }
    }

    public function where(...$args)
    {
        $this->runtimeConfig()->where($args);
        return $this;
    }

    public function join($joinTable, $joinCondition, $joinType = '')
    {
        $this->runtimeConfig()->join([
            $joinTable,$joinCondition,$joinType
        ]);
        return $this;
    }

    public function order(...$args)
    {
        $this->runtimeConfig()->order($args);
        return $this;
    }

    public function limit(int $one, ?int $two = null)
    {
        $this->runtimeConfig()->limit($one,$two);
        return $this;
    }

    public function field($fields)
    {
        $this->runtimeConfig()->field($fields);
        return $this;
    }

    public function groupBy($filed)
    {
        $this->runtimeConfig()->groupBy($filed);
        return $this;
    }

    public function withTotalCount()
    {
        $this->runtimeConfig()->withTotalCount();
        return $this;
    }

    public function findOne($pkVal = null):?AbstractModel
    {
        if($pkVal !== null){
            $pkName = $this->__tablePk();
            $this->where($pkName,$pkVal);
        }
        $this->limit(1);
        $builder = $this->__makeBuilder();
        $builder->get($this->tableName());
        $data = $this->__exec($builder);
        if($data){
            return new static($data[0]);
        }
        return null;
    }

    protected function mapOne(string $targetModelClass,$targetModeWhereCol,?string $currentModeWhereCol = null,string $joinType = "left")
    {
        $target = new \ReflectionClass($targetModelClass);
        if(!$target->isSubclassOf(AbstractModel::class)){
            throw new ExecuteFail("{$targetModelClass} not a subclass of ".AbstractModel::class);
        }
        if($currentModeWhereCol === null){
            $currentModeWhereCol = $this->__tablePk();
        }
        /** @var AbstractModel $target */
        $target = $target->newInstance();
        $builder = new QueryBuilder();
        $preData = $this->runtimeConfig()->getPreQueryData();
        if(!empty($preData)){
            $target->data($preData[$targetModelClass]);
            return $target;
        }

        if($this->runtimeConfig()->getPreQuery()){
            //说明已经执行了预查询
            if(isset($this->__preQueryData[$targetModelClass])){
                $whereVal = $this->getAttr($currentModeWhereCol);
                $data = $this->__preQueryData[$targetModelClass][$whereVal];
                $target->data($data);
                return $target;
            }
            //没有执行过，则构建执行
            //构建ids
            $ids = [];
            foreach ($this->lastQueryResult()->getResult() as $item){
                $ids[] = $item[$currentModeWhereCol];
            }

            // TODO  还没有处理别名
            $builder->join($this->tableName(),"{$target->tableName()}.{$targetModeWhereCol} = {$this->tableName()}.{$currentModeWhereCol}",$joinType);
            $builder->where("{$this->tableName()}.{$currentModeWhereCol}",$ids,"IN");
            $builder->limit($this->runtimeConfig()->getPreQuery()[1])->get($target->tableName());
            //返回以父类定义的where key的结果集
            $list = [];
            $data =  $this->__exec($builder,false);
            foreach ($data as $item){
                $list[$item[$targetModeWhereCol]] = $item;
            }
            return [
                "model"=>$targetModelClass,
                "dataList"=>$list,
                'parentCol'=>$currentModeWhereCol
            ];
        }else{
            $whereVal = $this->getAttr($currentModeWhereCol);
            $builder->where($targetModeWhereCol,$whereVal)->limit(2)->get($target->tableName());
            $data = $this->__exec($builder,false);
            if(!empty($data)){
                //如果存在两条记录，说明关联关系或者是数据库存储有问题
                if(count($data) > 1){
                    throw new ModelError(static::class." mapOne() to {$targetModelClass} relation error,more than one record match");
                }else{
                    $target->data($data[0]);
                    return $target;
                }
            }
        }
        return null;
    }

    public function all(?array $ids = null,?string $idsCol = null):array
    {
        $builder = $this->__makeBuilder();
        if($ids != null){
            if($idsCol == null){
                $idsCol = $this->__tablePk();
            }
            $builder->where($idsCol,$ids,"in");
        }

        $builder->get($this->tableName());

        $data = $this->__exec($builder);

        $rawWithResult = [];
        if($this->runtimeConfig()->getPreQuery()){
            //清空上次的执行结果
            $info = $this->runtimeConfig()->getPreQuery();
            $withCols = $info[0];
            foreach ($withCols as $col){
                $rawWithResult[] = call_user_func([$this,$col]);
            }
        }

        $list = [];

        foreach ($data as $item){
            $temp = new static();
            $temp->data($item);
            $tempArr = [];
            foreach ($rawWithResult as $withColResult){
                $keyVal = $this->__data[$withColResult['parentCol']];
                $tempArr[$withColResult['model']] = $withColResult['dataList'][$keyVal];
            }
            $temp->runtimeConfig()->setPreQueryData($tempArr);
            $list[] = $temp;
        }

        $this->resetStatusRuntimeStatus();

        return $list;
    }

    public function save():bool
    {
        $this->resetStatusRuntimeStatus();
    }

    public function update(array $data = [],bool $saveMode = true)
    {
        //$saveMode 是否允许无条件update
        $this->resetStatusRuntimeStatus();
    }

    public function preQuery(array $col,?int $limit = 65535):AbstractModel
    {
        $this->runtimeConfig()->setPreQuery([
            $col,$limit
        ]);
        return $this;
    }

    public function jsonSerialize()
    {

    }

    public function toArray(?array $callMethods = null):array
    {
        $data = $this->__tableArray();
        foreach ($data as $key => $val){
            $val = $this->getAttr($key);
            $data[$key] = $val;
        }
        if($callMethods == null){
            $callMethods = [];
        }
        foreach ($callMethods as $callMethod){
            if(method_exists($this,$callMethod)){
                $res = call_user_func([$this,$callMethod]);
                if(is_array($res)){
                    $data = $data + $res;
                }elseif($res instanceof AbstractModel){
                    $data = $data + $res->toArray($callMethods);
                }
            }
        }

        return $data;
    }

    function toRawArray():array
    {
        return $this->__data;
    }


    private function resetStatusRuntimeStatus()
    {
        $this->runtimeConfig()->reset();
    }


    private function __tableArray():array
    {
        $key = "tableArray".$this->__modelhash();
        $ret = RuntimeCache::getInstance()->get($key);
        if(is_array($ret)){
            return $ret;
        }
        $table = $this->schemaInfo();
        $data = $table->getColumns();
        $list = [];
        /** @var Column $col */
        foreach ($data as $col){
            $list[$col->getColumnName()] = $col->getDefaultValue();
        }
        RuntimeCache::getInstance()->set($key,$list);
        return $list;
    }

    function __tablePk():?string
    {
        $key = "tablePk".$this->__modelhash();
        $ret = RuntimeCache::getInstance()->get($key);
        if($ret){
            return $ret;
        }

        $keyCol = null;
        $table = $this->schemaInfo();
        /** @var Column $column */
        foreach ($table->getColumns() as $column){
            if($column->getIsPrimaryKey()){
                $keyCol = $column->getColumnName();
                RuntimeCache::getInstance()->set($key,$keyCol);
                break;
            }
        }
        if($keyCol == null){
            throw new ExecuteFail("table: {$this->tableName()} have no primary key");
        }

        return $keyCol;
    }

    private function __modelHash():string
    {
        $key = md5(static::class.$this->tableName().$this->runtimeConfig()->getConnectionConfig()->getName());
        return substr($key,8,16);
    }

    private function __exec(QueryBuilder $builder,bool $resetQueryResult = true)
    {
        $client = $this->runtimeConfig()->getClient();

        if($resetQueryResult){
            $this->__lastQueryResult = DbManager::getInstance()
                ->__exec($client,$builder,false,$this->runtimeConfig()->getConnectionConfig()->getTimeout());

            return $this->__lastQueryResult->getResult();
        }else{
            return DbManager::getInstance()
                ->__exec($client,$builder,false,$this->runtimeConfig()->getConnectionConfig()->getTimeout())->getResult();
        }
    }

    private function __makeBuilder():QueryBuilder
    {
        //构建query builder
        $builder = new QueryBuilder();
        if($this->runtimeConfig()->getWithTotalCount()){
            $builder->withTotalCount();
        }
        foreach ($this->runtimeConfig()->getOrder() as $order){
            $builder->orderBy(...$order);
        }
        foreach ($this->runtimeConfig()->getWhere() as $where){
            $builder->where(...$where);
        }

        foreach ($this->runtimeConfig()->getGroupBy() as $group){
            $builder->groupBy($group);
        }
        foreach ($this->runtimeConfig()->getJoin() as $join){
            $builder->join(...$join);
        }
        if($this->runtimeConfig()->getLimit()){
            $builder->limit($this->runtimeConfig()->getLimit());
        }
        return $builder;
    }
}