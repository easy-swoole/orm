<?php


namespace EasySwoole\TpORM\Characteristic;


trait Iterator
{
    use Base;
    private $iteratorKey;
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

}