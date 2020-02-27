<?php
/**
 * 时间戳处理器
 * User: Siam
 * Date: 2020/2/27
 * Time: 11:44
 */

namespace EasySwoole\ORM\Utility;


use EasySwoole\ORM\AbstractModel;

class TimeStampHandle
{

    /**
     * 处理时间戳
     * @param AbstractModel $model
     * @param $data
     * @param string $doType
     * @return mixed
     * @throws \EasySwoole\ORM\Exception\Exception
     */
    public static function preHandleTimeStamp(AbstractModel $model, $data, $doType = 'insert')
    {
        if ($model->getAutoTimeStamp() === false){
            return $data;
        }
        $type = 'int';

        if ($model->getAutoTimeStamp() === 'datetime'){
            $type = 'datetime';
        }

        $createTime = $model->getCreateTime();
        $updateTime = $model->getUpdateTime();
        switch ($doType){
            case 'insert':
                if ($createTime !== false){
                    $tem = static::parseTimeStamp(time(), $type);
                    $model->setAttr($createTime, $tem);
                    $data[$createTime] = $tem;
                }
                if ($updateTime !== false){
                    $tem = static::parseTimeStamp(time(), $type);
                    $model->setAttr($updateTime, $tem);
                    $data[$updateTime] = $tem;
                }
                break;
            case 'update':
                if ($updateTime !== false){
                    $tem = static::parseTimeStamp(time(), $type);
                    $model->setAttr($updateTime, $tem);
                    $data[$updateTime] = $tem;
                }
                break;
        }

        return $data;
    }

    private static function parseTimeStamp(int $timestamp, $type = 'int')
    {
        switch ($type){
            case 'int':
                return $timestamp;
                break;
            case 'datetime':
                return date('Y-m-d H:i:s', $timestamp);
                break;
            default:
                return date($type, $timestamp);
                break;
        }
    }
}