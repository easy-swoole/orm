<?php


namespace EasySwoole\TpORM;


class ColumnType
{
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_STRING = 2;
    const COLUMN_TYPE_FLOAT = 3;

    public static function valueMap($data,int $type)
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