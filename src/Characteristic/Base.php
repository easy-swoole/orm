<?php


namespace EasySwoole\ORM\Characteristic;


use EasySwoole\ORM\Utility\Column;

trait Base
{
    private $data = [];
    private $schemaInfo = [];
    private $strict = false;

    function __construct(?array $data = null)
    {
        $this->initialize();
        if($data){
            $this->setData($data);
        }
    }

    protected function initialize()
    {

    }

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

    public static function create(array $data = null)
    {
        return new static($data);
    }

    public function setData(array $data,bool $clear = false)
    {
        if($clear){
            $this->data = [];
        }
        if($this->strictScheme()){
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
}