<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2019/11/15
 * Time: 17:33
 */

namespace EasySwoole\ORM\Tests\models;


use EasySwoole\Mysqli\QueryBuilder;
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

    public function hasOneEqName($name){
        return $this->hasOne(TestUserListModel::class, function (QueryBuilder $queryBuilder) use ($name){
            $queryBuilder->where('name', $name);
            return $queryBuilder;
        }, 'name', 'name');
    }

    public function hasManyEqName($data){
        $name = $data[0];
        return $this->hasMany(TestUserListModel::class, function (QueryBuilder $queryBuilder) use ($name){
            $queryBuilder->where('name', $name);
            return $queryBuilder;
        }, 'name', 'name');
    }

    public function has_many_where()
    {
        return $this->hasMany(TestUserListModel::class, function (QueryBuilder $builder){
            $builder->where("age", 21);
        }, 'name', 'name');
    }

    public function has_many()
    {
        return $this->hasMany(TestUserListModel::class, null, 'name', 'name');
    }

    /**
     * 非模型属性字段 获取器，可用于append
     */
    public function getAppendOneAttr()
    {
        return "siam_append";
    }
}