<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2020/2/27
 * Time: 10:01
 */

namespace EasySwoole\ORM\Tests\models;


use EasySwoole\ORM\AbstractModel;

class Users extends AbstractModel
{
    protected $tableName='users';

    public function roles()
    {
		// 被关联模型--关联的中间表--筛选条件--中间表中本模型外键名--中间表中子模型外键名--本模型主键名--子模型主键名--关联关系
        return $this->belongsToMany(Roles::class, 'user_role', null, 'user_id', 'role_id', 'user_id', 'role_id');
    }
}