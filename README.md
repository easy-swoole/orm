# Easyswoole-ORM

## 单元测试
```php
 ./vendor/bin/co-phpunit tests
```

样例数据库使用了mysql著名的employees样例库: https://github.com/datacharmer/test_db

## 基础使用

定义一个模型

```php

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
     * 此处需要返回一个 EasySwoole\ORM\Utility\Schema\Table
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

```

使用ORM

```php

<?php

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Driver\MysqlConfig;
use EasySwoole\ORM\Driver\MysqlDriver;
use EasySwoole\ORM\Tests\UserModel;
use Swoole\Coroutine;

require_once 'vendor/autoload.php';

$mysqlConf = [
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'user'     => 'root',
    'password' => '',
    'database' => 'employees',
];

// Add default connection
DbManager::getInstance()->addConnection(new MysqlDriver(new MysqlConfig($mysqlConf)));

// Use connection in coroutine
Coroutine::create(function () {

    // 实例化使用
    $model = new UserModel([
        'emp_no'    => 10001,
        'dept_no'   => 'd005',
        'from_date' => '1986-06-26',
        'to_date'   => '9999-01-01'
    ]);
    $model->setAttr('emp_no', 10002);
    $model->emp_no = 10003;
    $model->save(); // 未实现

    // 静态获取(返回一个模型)
    UserModel::get(1);
    UserModel::get(['emp_no' => 10001]);

    // 静态获取(多条/返回一个模型数组)
    UserModel::all([10001, 10002, 10003]);
    UserModel::all('10001,10002,10003');
    UserModel::all(['emp_no' => 10001]);
    UserModel::all(function (QueryBuilder $builder) {
        $builder->where('emp_no', 1);
    });

    // 静态创建(返回一个模型/可以直接从返回模型的字段读取InsertId) 未实现
    UserModel::create([
        'dept_no'   => 'd005',
        'from_date' => '1986-06-26',
        'to_date'   => '9999-01-01'
    ]);

    // 静态更新(返回更新后的模型) 未实现
    UserModel::update([
        'dept_no'   => 'd005',
        'from_date' => '1986-06-26',
        'to_date'   => '9999-01-01'
    ], ['emp_no' => 10001]);

    // 静态删除(返回影响的记录数) 未实现
    UserModel::destroy(10001);
    UserModel::destroy('10001,10002,10003');
    UserModel::destroy([10001, 10002, 10003]);
    UserModel::destroy(['emp_no' => 10001]);
    UserModel::destroy(function (QueryBuilder $builder) {
        $builder->where('emp_no', 1);
    });

});

```