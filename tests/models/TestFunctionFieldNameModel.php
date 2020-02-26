<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2019/12/16
 * Time: 15:27
 */

namespace EasySwoole\ORM\Tests\models;


use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Utility\Schema\Table;

class TestFunctionFieldNameModel extends AbstractModel
{
    protected $tableName = 'test_field_name';

    public function schemaInfo(bool $isCache = TRUE): Table
    {
        $table = new Table('test_field_name');
        $table->colInt('id')->setIsPrimaryKey(true);
        $table->colChar('name', 255);
        $table->colChar('order', 255);
        $table->colInt('age');
        return $table;
    }
}