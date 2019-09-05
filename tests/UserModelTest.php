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
}