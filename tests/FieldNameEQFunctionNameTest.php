<?php
/**
 * 表字段有ORM函数同名，测试取出数据（修复前getAttr有bug 会当成关联查询）
 * User: Siam
 * Date: 2019/12/16
 * Time: 15:27
 */

namespace EasySwoole\ORM\Tests;


use PHPUnit\Framework\TestCase;


use EasySwoole\ORM\Tests\models\TestFunctionFieldNameModel;

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