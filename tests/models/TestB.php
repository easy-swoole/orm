<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


namespace EasySwoole\ORM\Tests\models;


use EasySwoole\ORM\AbstractModel;

class TestB extends AbstractModel
{
    protected $tableName = 'test_b';

    public function getBNameAttr($value, $data)
    {
        return $value . '-bar-b';
    }

    public function getCNameAttr($value, $data)
    {
        return $value . '-bar-c';
    }
}