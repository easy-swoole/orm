<?php


namespace EasySwoole\ORM\Tests;


use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    function testCreate()
    {
        $this->assertEquals(1, UserModel::create([
           'userId'=>1
        ])->userId);
        $this->assertEquals(['userId'=>1,'userName'=>null,'userAccount'=>null], UserModel::create([
            'userId'=>1
        ])->toArray());
        $this->assertEquals(['userId'=>1], UserModel::create([
            'userId'=>1
        ])->toArray(true));
    }

    function testFind()
    {
        $model = UserModel::create();
        $model->find(1);
        $this->assertEquals('SELECT  * FROM user_list WHERE  userId = 1  LIMIT 1',$model->lastQuery);
        $model->find();
        $this->assertEquals('SELECT  * FROM user_list LIMIT 1',$model->lastQuery);
    }
}