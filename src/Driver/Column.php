<?php


namespace EasySwoole\ORM\Driver;


class Column
{
    const TYPE_INT = 1;
    const TYPE_STRING = 2;
    const TYPE_FLOAT = 3;

    public static function valueMap($data,int $type)
    {
        switch ($type){
            case self::TYPE_INT:{
                return (int)$data;
                break;
            }
            case self::TYPE_STRING:{
                return (string)$data;
                break;
            }
            case  self::TYPE_FLOAT:{
                return (float)$data;
                break;
            }
            default:{
                return $data;
            }
        }
    }
}