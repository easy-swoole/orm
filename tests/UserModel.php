<?php

namespace EasySwoole\ORM\Tests;

use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Model\Schema\Table;

/**
 * 用于测试的用户模型
 * Class UserModel
 * @package EasySwoole\ORM\Tests
 */
class UserModel extends AbstractModel
{
    /**
     * 表的定义
     * @return Table
     */
    protected function schemaInfo(): Table
    {
        $table = new Table('dept_emp');
        $table->colInt('emp_no')->setIsPrimaryKey(true);
        $table->colChar('dept_no', 4);
        $table->colDate('from_date');
        $table->colDate('to_date');
        return $table;
    }

}