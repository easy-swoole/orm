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
class TestUserListModel extends AbstractModel
{
    protected $tableName='user_test_list';

    /**
     * 非模型属性字段 获取器，可用于append
     */
    public function getAppendOneAttr()
    {
        return "siam_append";
    }
}