<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2019/12/16
 * Time: 15:27
 */

namespace EasySwoole\ORM\Tests;


use PHPUnit\Framework\TestCase;

class FieldNameEQFunctionNameTest extends TestCase
{
    public function testOrder()
    {
        $model = TestFunctionFieldNameModel::create()->data([
            'name'  => 'siam',
            'order' => 1,
            'age'   => 21,
        ]);
        $this->assertEquals('{"name":"siam","order":"1","age":21}', json_encode($model));
    }
}