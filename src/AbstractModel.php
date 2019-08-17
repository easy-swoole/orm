<?php


namespace EasySwoole\TpORM;


use EasySwoole\Mysqli\Mysqli;

abstract class AbstractModel implements \ArrayAccess
{
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_STRING = 2;
    const COLUMN_TYPE_FLOAT = 3;

    private $data = [];

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

    protected function schemaType():array
    {
        return [];
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

    function save(array $data)
    {

    }

    function saveAll()
    {

    }

    function allowField()
    {

    }

    public static function create(array $data = []):AbstractModel
    {
        $instance = new static();
        $instance->setData($data);
        return $instance;
    }

    public function setData(array $data)
    {
        $this->data = [];
    }
    /**
     * @param        $whereProps
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

    public function update()
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

    public function order()
    {

    }

    public function value()
    {

    }

    public function column()
    {

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

    public static function __callStatic($name, $arguments)
    {
        /*
         * 用以实现静态调用
         */
    }

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

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}