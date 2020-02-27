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
}