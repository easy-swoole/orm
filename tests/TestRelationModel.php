<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2019/11/15
 * Time: 17:33
 */

namespace EasySwoole\ORM\Tests;


use EasySwoole\ORM\AbstractModel;

/**
 * Class TestUserModel
 * @package EasySwoole\ORM\Tests
 * @property $id
 * @property $name
 * @property $age
 * @property $addTime
 * @property $state
 */
class TestRelationModel extends AbstractModel
{
    protected $tableName='test_user_model';

    public function user_list()
    {
        return $this->hasOne(TestUserListModel::class, null, 'name', 'name');
    }

    public function has_many()
    {
        return $this->hasMany(TestUserListModel::class, null, 'name', 'name');
    }
}