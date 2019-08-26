<?php


namespace EasySwoole\TpORM\Characteristic;


trait Base
{
    private $data = [];
    private $schemaInfo = [];

    function __construct(?array $data = null)
    {
        if($data){
            $this->setData($data);
        }
    }

    public static function create(array $data = null)
    {
        return new static($data);
    }

    public function setData(array $data)
    {

    }
}