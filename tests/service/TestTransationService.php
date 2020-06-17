<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2020/6/8
 * Time: 10:18
 */

namespace EasySwoole\ORM\Tests\service;


use EasySwoole\ORM\DbManager;

class TestTransationService
{

    public static $res = true;
    public static function getUser()
    {
        DbManager::getInstance()->startTransactionWithCount();
        $res = static::$res;
        if ($res){
            DbManager::getInstance()->commitWithCount();
            return true;
        }
        DbManager::getInstance()->rollbackWithCount();
        return false;
    }
}