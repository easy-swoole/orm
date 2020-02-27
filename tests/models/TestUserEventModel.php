<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2019/10/22 0022
 * Time: 15:08
 */

namespace EasySwoole\ORM\Tests\models;


use EasySwoole\ORM\AbstractModel;
use EasySwoole\Utility\Str;

/**
 * Class TestUserModel
 * @package EasySwoole\ORM\Tests
 * @property $id
 * @property $name
 * @property $age
 * @property $addTime
 * @property $state
 */
class TestUserEventModel extends AbstractModel
{
    protected $tableName = 'test_user_model';

    public static $insert = false;
    public static $update = false;
    public static $delete = false;

    protected static function onBeforeInsert($model)
    {
        return self::$insert;
    }

    protected static function onAfterInsert($model, $res)
    {

    }

    protected static function onBeforeUpdate($model)
    {
        return self::$update;
    }

    protected static function onAfterUpdate($model, $res)
    {

    }

    protected static function onBeforeDelete()
    {
        return self::$delete;
    }

    public static function onAfterDelete($model, $res)
    {

    }
}