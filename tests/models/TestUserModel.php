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
class TestUserModel extends AbstractModel
{
    protected $tableName='test_user_model';
}