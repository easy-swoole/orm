<?php


namespace EasySwoole\TpORM;


use EasySwoole\Mysqli\Mysqli;

abstract class AbstractModel implements \ArrayAccess,\JsonSerializable,\Iterator
{
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_STRING = 2;
    const COLUMN_TYPE_FLOAT = 3;

    private $data = [];
    private $iteratorKey;

    abstract function tableName():string ;

    abstract function mysqliConnection():Mysqli;

    abstract function schemaInfo():array ;

    protected function prefix():string
    {
        return '';
    }

    public static function primaryKey():?string
    {
        return null;
    }

    function find($pkValue = null)
    {
        if($pkValue && !empty(static::primaryKey())){
            $this->where(static::primaryKey(),$pkValue);
        }
        $data = $this->mysqliConnection()->getOne($this->prefix().$this->tableName());
        if(!empty($data)){
            $this->setData($data);
        }
        return $this;
    }

    /*
     * insert
     */
    function save()
    {
        return $this->mysqliConnection()->insert($this->prefix().$this->tableName(),$this->data);
    }

    function saveAll()
    {

    }


    public static function create(array $data = []):AbstractModel
    {
        $instance = new static();
        $instance->setData($data);
        return $instance;
    }

    public function setData(array $data,bool $clearData = true):AbstractModel
    {
        if($clearData){
            $this->data = [];
        }
        foreach ($this->schemaInfo() as $key => $type){
            if(isset($data[$key])){
                $this->data[$key] = $this->valueMap($data[$key],$type);
            }
        }
        return $this;
    }

    /**
     * @param $whereProps
     * @param string $whereValue
     * @param string $operator
     * @param string $cond
     * @return AbstractModel
     */
    public function where( $whereProps, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND' ):AbstractModel
    {
        $this->mysqliConnection()->where($whereProps,$whereValue,$operator,$cond);
        return $this;
    }

    public function update(array $data = [],array $columns = null)
    {

    }

    public function delete()
    {

    }

    public function select()
    {

    }

    public function limit()
    {

    }

    public function order( string $orderByField, string $orderByDirection = "DESC", $customFieldsOrRegExp = null ):AbstractModel
    {
        $this->mysqliConnection()->orderBy($orderByField,$orderByDirection,$customFieldsOrRegExp);
        return $this;
    }


    function hasOne(string $class,?string $foreignKey = null,?string $primaryKey = null)
    {
        //判断class是否是存在的model class
        if($primaryKey === null){
            $primaryKey = self::primaryKey();
        }
        if($foreignKey === null){
            $foreignKey = $class::primaryKey();
        }
        //做关联主键空判
        //执行join get one
        //return $class($data);
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
        if(!in_array($offset,$this->schemaInfo())){
            return false;
        }
        $this->data[$offset] = $value;
        return true;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /*
        * ************ Iterator *************
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
       * ************ JsonSerializable *************
    */

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function toArray():array
    {
        return $this->data;
    }


    private function valueMap($data,int $type)
    {
        switch ($type){
            case self::COLUMN_TYPE_INT:{
                return (int)$data;
                break;
            }
            case self::COLUMN_TYPE_STRING:{
                return (string)$data;
                break;
            }
            case  self::COLUMN_TYPE_FLOAT:{
                return (float)$data;
                break;
            }
            default:{
                return $data;
            }
        }
    }
}