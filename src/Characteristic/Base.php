<?php


namespace EasySwoole\ORM\Characteristic;


use EasySwoole\ORM\Utility\Column;

trait Base
{
    protected $data = [];
    protected $schemaInfo = [];
    protected $strict = false;

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