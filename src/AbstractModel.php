<?php


namespace EasySwoole\ORM;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Driver\Result;
use EasySwoole\ORM\Exception\Exception;

/**
 * Class AbstractModel
 * @package EasySwoole\ORM
 */
abstract class AbstractModel implements \ArrayAccess,\Iterator,\JsonSerializable
{

    protected $data = [];
    protected $schemaInfo = [];
    protected $strict = false;

    private $iteratorKey;

    protected $connection = 'default';

    protected $pk = null;

    private $queryBuilder;

    private $queryResult;

    private $limit = null;

    private $withTotalCount = false;

    private $fields = "*";

    function __construct()
    {
        $this->queryBuilder = new QueryBuilder();
        $this->initialize();
    }

    abstract protected function table():string ;

    protected function initialize()
    {

    }

    function connect(string $name)
    {
        $this->connection = $name;
        return $this;
    }

    public static function create(array $data = null)
    {
        $ret = new static();
        if($data){
            $ret->data($data,true);
        }
        return $ret;
    }

    function find($data = null)
    {
        if($data !== null && !empty($this->pk)){
            $this->where($this->pk,$data);
        }
        $this->queryBuilder()->getOne($this->table());
        $data = [];
        $ret = $this->execQueryBuilder();
        if($ret){
            $data = $ret[0];
        }
        $this->data($data);
        return $this;
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
        if(!$this->withTotalCount){
            $this->queryBuilder()->withTotalCount();
            $this->withTotalCount = true;
        }
        return $this;
    }

    function save(?array $data = null)
    {
        if(empty($data)){
            $data = $this->data;
        }
        $this->insert($data);
    }

    function field($fields)
    {
        if(!is_array($fields)){
            $fields = [$fields];
        }
        $this->fields = $fields;
    }

    public function queryBuilder():QueryBuilder
    {
        return $this->queryBuilder;
    }

    protected function execQueryBuilder()
    {
        return $this->query($this->queryBuilder()->getLastPrepareQuery(),$this->queryBuilder()->getLastBindParams());
    }

    protected function query(string $sql,array $bindParams = [])
    {
        $ret = DbManager::getInstance()->execPrepareQuery($sql,$bindParams,$this->connection);
        if($ret){
            $this->queryResult = $ret;
            if($ret->getLastErrorNo()){
                throw new Exception($ret->getLastError());
            }else{
                if($this->withTotalCount){
                    $data = DbManager::getInstance()->execPrepareQuery('SELECT FOUND_ROWS() as count',[],$this->connection);
                    if($data->getResult()){
                        $ret->setTotalCount($data->getResult()[0]['count']);
                    }
                }
                return $ret->getResult();
            }
        }
        $this->reset();
        return null;
    }

    public function getQueryResult():?Result
    {
        return $this->queryResult;
    }

    function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->queryBuilder->where($whereProp, $whereValue, $operator, $cond);
        return $this;
    }

    function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        $this->where($whereProp, $whereValue, $operator, 'OR');
        return $this;
    }

    function join($joinTable, $joinCondition, $joinType = '')
    {
        $this->queryBuilder->join($joinTable, $joinCondition, $joinType);
        return $this;
    }

    function get($data = null)
    {
        if($data !== null && !empty($this->pk)){
            $this->where($this->pk,$data);
        }
        $this->queryBuilder()->get($this->table(),$this->limit,$this->fields);
        return $this->execQueryBuilder();
    }

    function delete($data = null)
    {
        if($data !== null && !empty($this->pk)){
            $this->where($this->pk,$data);
        }
        $this->queryBuilder()->delete($this->table(),$this->limit);
        return $this->execQueryBuilder();
    }

    function update(?array $data = null)
    {
        if(!$data){
            $data = $this->data;
        }
        if(is_array($this->fields)){
            foreach ($data as $key => $val){
                if(!in_array($key,$this->fields)){
                    unset($data[$key]);
                }
            }
        }
        $this->queryBuilder()->update($this->table(),$data,$this->limit);
        $temp = $this->execQueryBuilder();
        if($this->getQueryResult()->getAffectedRows()){
            return $this->getQueryResult()->getAffectedRows();
        }else{
            return $temp;
        }
    }

    public function insert(?array $data = null)
    {
        if(!$data){
            $data = $this->data;
        }
        $this->queryBuilder()->insert($this->table(),$data);
        $temp = $this->execQueryBuilder();
        if($this->getQueryResult()->getLastInsertId()){
            return $this->getQueryResult()->getLastInsertId();
        }else{
            return $temp;
        }
    }


    public function data(array $data, bool $clear = false)
    {
        if($clear){
            $this->data = [];
        }
        if($this->strict){
            foreach ($data as $key => $val){
                if(isset($this->schemaInfo[$key])){
                    $this->data[$key] = Column::valueMap($val,$this->schemaInfo[$key]);
                }
            }
        }else{
            foreach ($data as $key => $val){
                $this->data[$key] = $val;
            }
        }
    }




    /*
     * JsonSerializable
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    public function toArray():array
    {
        return $this->data;
    }


    /*
     * Iterator
     */

    public function current()
    {
        return $this->data[$this->iteratorKey];
    }

    public function next()
    {
        $temp = array_keys($this->data);
        while ($tempKey = array_shift($temp)){
            if($tempKey === $this->iteratorKey){
                $this->iteratorKey = array_shift($temp);
                break;
            }
        }
        return $this->iteratorKey;
    }

    public function key()
    {
        return $this->iteratorKey;
    }

    public function valid()
    {
        return isset($this->data[$this->iteratorKey]);
    }

    public function rewind()
    {
        $temp = array_keys($this->data);
        $this->iteratorKey = array_shift($temp);
    }


    /*
     * BASE
     */
    protected function strictScheme(bool $strict = null)
    {
        if($strict !== null){
            $this->strict = $strict;
        }
        return $this->strict;
    }

    protected function schemaInfo(array $info = null)
    {
        if($info){
            /*
             * 修改了scheme的时候，需要重置数据
             */
            $this->schemaInfo = $info;
            $this->data = [];
        }
        return $this->schemaInfo;
    }




    /*
     * ************** Attribute ****************
     */
    function __set($name, $value)
    {
        if($this->strict){
            if(isset($this->schemaInfo[$name])){
                $this->data[$name] = Column::valueMap($value,$this->schemaInfo[$name]);
            }
        }else{
            $this->data[$name] = $value;
        }
    }

    function __get($name)
    {
        if(isset($this->data[$name])){
            return $this->data[$name];
        }
        return null;
    }

    /*
     * ************ ArrayAccess *************
     */

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if(isset($this->data[$offset])){
            return $this->data[$offset];
        }else{
            return null;
        }
    }

    public function offsetSet($offset, $value):bool
    {
        if(!in_array($offset,$this->schemaInfo)){
            return false;
        }
        $this->data[$offset] = $value;
        return true;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }







    private function reset()
    {
        $this->limit = null;
        $this->queryBuilder()->reset();
        $this->withTotalCount = false;
        $this->fields = null;
    }
}