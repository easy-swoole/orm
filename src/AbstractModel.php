<?php


namespace EasySwoole\TpORM;


abstract class AbstractModel
{
    abstract function setTableName():string ;
    protected function setPrefix():string
    {
        return '';
    }
    protected function setPrimaryKey():?string
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

    public static function __callStatic($name, $arguments)
    {
        /*
         * 用以实现静态调用
         */
    }
}