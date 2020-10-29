<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


namespace EasySwoole\ORM\Tests\models;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;

class TestA extends AbstractModel
{
    protected $tableName = 'test_a';

    public function hasOneList()
    {
        return $this->hasOne(TestB::class, function (QueryBuilder $queryBuilder) {
            $queryBuilder->join('test_c', 'test_b.id = test_c.b_id', 'left');
        }, 'id', 'a_id');
    }

    public function hasManyList()
    {
        return $this->hasMany(TestB::class, function (QueryBuilder $queryBuilder) {
            $queryBuilder->join('test_c', 'test_b.id = test_c.b_id', 'left');
        }, 'id', 'a_id');
    }

    public function hasOneJoinList()
    {
        return $this->hasOne(TestC::class, function (QueryBuilder $queryBuilder) {
            $queryBuilder->fields('*,test_c.id as cid');
        }, 'bid', 'b_id');
    }

    public function hasManyJoinList()
    {
        return $this->hasMany(TestC::class, function (QueryBuilder $queryBuilder) {
            $queryBuilder->fields('*,test_c.id as cid');
        }, 'bid', 'b_id');
    }
}