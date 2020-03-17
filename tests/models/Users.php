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
        return $this->belongsToMany(Roles::class, 'user_role');
    }

    public function roles_different_field()
    {
        return $this->belongsToMany(Roles::class, 'user_role_different_field', 'u_id', 'r_id');
    }

    public function roles_different_field_call()
    {
        return $this->belongsToMany(Roles::class, 'user_role_different_field', 'u_id', 'r_id', function($model){
            // 是目标表
            $model->field("role_id");
        });
    }
}