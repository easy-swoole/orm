<?php


namespace EasySwoole\TpORM;


use EasySwoole\Mysqli\Mysqli;

abstract class AbstractModel
{
    abstract function setTableName():string ;
    abstract function mysqliConnection():Mysqli;
    protected function setPrefix():string
    {
        return '';
    }
    public static function primaryKey():?string
    {
        return null;
    }

    protected function setSchema():array
    {
        return [];
    }

    protected function setSchemaType():array
    {
        return [];
    }

    function find()
    {

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

    public static function create(array $data = [])
    {

    }

    public function where()
    {

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
}