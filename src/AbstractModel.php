<?php


namespace EasySwoole\ORM;

use ArrayAccess;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\Result;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Utility\PreProcess;
use EasySwoole\ORM\Utility\Schema\Table;
use EasySwoole\Spl\SplString;
use JsonSerializable;

/**
 * 抽象模型
 * Class AbstractModel
 * @package EasySwoole\ORM
 */
abstract class AbstractModel implements ArrayAccess, JsonSerializable
{

    private $lastQueryResult;
    private $lastQuery;
    private $limit = null;
    private $withTotalCount = false;
    private $fields = "*";

    /** @var Table */
    private $schemaInfo;
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
    private $data;

    /**
     * 模型的原始数据
     * 未应用修改器和获取器之前的原始数据
     * @var array
     */
    private $originData;

    private $onQuery;

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

    public function onQuery(callable $call):AbstractModel
    {
        $this->onQuery = $call;
        return $this;
    }

    public function lastQueryResult():?Result
    {
        return $this->lastQueryResult;
    }

    public function lastQuery():?QueryBuilder
    {
        return $this->lastQuery;
    }

    function limit(int $one,?int $two = null)
    {
        if($two !== null){
            $this->limit = [$one,$two];
        }else{
            $this->limit = $one;
        }
        return $this;
    }

    function withTotalCount()
    {
        $this->withTotalCount = true;
        return $this;
    }

    function field($fields)
    {
        if(!is_array($fields)){
            $fields = [$fields];
        }
        $this->fields = $fields;
        return $this;
    }

    function __construct(array $data = [])
    {
        $this->schemaInfo = $this->schemaInfo();
        $this->data($data);
    }

    function connection(string $name,bool $isTemp = false):AbstractModel
    {
        if($isTemp){
            $this->tempConnectionName = $name;
        }else{
            $this->connectionName = $name;
        }
        return $this;
    }


    public function getAttr($attrName)
    {
        // 是否有获取器
        $nameSpl  = new SplString($attrName);
        $method   = 'get' . $nameSpl->studly()->__toString() . 'Attr';
        if (method_exists($this, $method)) {
            return $this->$method($this->data[$attrName] ?? null, $this->data);
        }
        return $this->data[$attrName] ?? null;
    }


    public function setAttr($attrName, $attrValue):bool
    {
        if(isset($this->getSchemaInfo()->getColumns()[$attrName])){
            $col = $this->getSchemaInfo()->getColumns()[$attrName];
            $attrValue = PreProcess::dataValueFormat($attrValue,$col);
            // 是否有修改器
            $nameSpl  = new SplString($attrName);
            $method    = 'set' . $nameSpl->studly()->__toString() . 'Attr';
            if (method_exists($this, $method)) {
                $attrValue = $this->$method($attrValue, $this->data);
            }
            $this->data[$attrName] = $attrValue;
            return true;
        }else{
            return false;
        }
    }

    public function data(array $data)
    {
        foreach ($data as $key => $value){
            $this->setAttr($key, $value);
        }
        $this->originData = $this->data;
        return $this;
    }

    public function destroy($where = null):?int
    {
        $builder = new QueryBuilder();
        $builder = PreProcess::mappingWhere($builder,$where,$this);
        $builder->delete($this->getSchemaInfo()->getTable(),$this->limit);
        $this->query($builder);
        return $this->lastQueryResult()->getAffectedRows();
    }

    /**
     * 保存 插入
     * @param bool $notNul
     * @throws Exception
     * @throws \Throwable
     * @return bool|int
     */
    public function save($notNul = false)
    {
        $builder = new QueryBuilder();
        $primaryKey = $this->getSchemaInfo()->getPkFiledName();
        if(empty($primaryKey)){
            throw new Exception('save() needs primaryKey for model '.static::class);
        }
        $rawArray = $this->toArray($notNul);
        $builder->insert($this->getSchemaInfo()->getTable(),$rawArray);
        $this->query($builder);

        if ($this->lastQueryResult()->getResult() === false){
            return false;
        }

        if($this->lastQueryResult()->getLastInsertId()){
            $this->data[$primaryKey] = $this->lastQueryResult()->getLastInsertId();
            $this->originData = $this->data;
            return $this->lastQueryResult()->getLastInsertId();
        }
        return true;
    }

    /**
     * 获取数据
     * @param null $where
     * @return AbstractModel|null
     * @throws Exception
     * @throws \Throwable
     */
    public function get($where = null)
    {
        $modelInstance = new static;
        $builder = new QueryBuilder;
        $builder =  PreProcess::mappingWhere($builder,$where,$modelInstance);
        $builder->getOne($modelInstance->getSchemaInfo()->getTable(),$this->fields);

        $res = $this->query($builder)[0];

        if ($res === null){
            return null;
        }

        $modelInstance->data($res);
        return $modelInstance;
    }

    protected function query(QueryBuilder $builder,bool $raw = false)
    {
        $start = microtime(true);
        $this->lastQuery = clone $builder;
        if($this->tempConnectionName){
            $connectionName = $this->tempConnectionName;
        }else{
            $connectionName = $this->connectionName;
        }
        try{
            if($this->withTotalCount){
                $builder->withTotalCount();
            }
            $ret = DbManager::getInstance()->query($connectionName,$builder,$raw);
            if($this->onQuery){
                $temp = clone $builder;
                call_user_func($this->onQuery,$ret,$temp,$start);
            }
            $builder->reset();
            $this->lastQueryResult = $ret;
            return $ret->getResult();
        }catch (\Throwable $throwable){
            throw $throwable;
        }finally{
            $this->reset();
        }
    }

    /**
     * 批量查询
     * @param null $where
     * @param bool $returnAsArray
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    public function all($where = null, bool $returnAsArray = false):array
    {
        $builder = new QueryBuilder;
        $builder = PreProcess::mappingWhere($builder,$where,$this);
        $builder->get($this->getSchemaInfo()->getTable(),$this->limit,$this->fields);
        $results = $this->query($builder);
        $resultSet = [];
        if (is_array($results)) {
            foreach ($results as $result) {
                if($returnAsArray){
                    $resultSet[] = $result;
                }else{
                    $resultSet[] = static::create($result);
                }
            }
        }
        return $resultSet;
    }

    public static function create(array $data = []):AbstractModel
    {
        return new static($data);
    }


    /**
     * 更新
     * @param array $data
     * @param null $where
     * @return bool
     * @throws Exception
     * @throws \Throwable
     */
    public function update(array $data = [], $where = null)
    {
        if(empty($data)){
            // $data = $this->toArray();
            $data = array_diff($this->data, $this->originData);
            if (empty($data)){
                return true;
            }
        }
        $builder = new QueryBuilder();
        if($where){
            PreProcess::mappingWhere($builder,$where,$this);
        }else{
            $pk = $this->getSchemaInfo()->getPkFiledName();
            if(isset($this->data[$pk])){
                $pkVal = $this->data[$pk];
                $builder->where($pk,$pkVal);
            }else{
                throw new Exception("update error,pkValue is require");
            }
        }
        $builder->update($this->getSchemaInfo()->getTable(), $data);
        $results = $this->query($builder);

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

    public function offsetSet($offset, $value)
    {
        return $this->setAttr($offset, $value);
    }


    public function offsetUnset($offset)
    {
        return $this->setAttr($offset, null);
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function toArray($notNul = false): array
    {
        $temp = $this->data;
        if ($notNul) {
            foreach ($temp as $key => $value) {
                if ($value === null) {
                    unset($temp[$key]);
                }
            }
            return $temp;
        }
        if(is_array($this->fields)){
            foreach ($temp as $key => $value){
                if(in_array($key,$this->fields)){
                    unset($temp[$key]);
                }
            }
        }
        return $temp;
    }

    public function __toString()
    {
        return json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    function __set($name, $value)
    {
        $this->setAttr($name, $value);
    }

    function __get($name)
    {
        return $this->getAttr($name);
    }

    protected function reset()
    {
        $this->fields = '*';
        $this->limit = null;
        $this->withTotalCount = false;
        $this->tempConnectionName = null;
    }
}