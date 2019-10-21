Easyswoole-ORM

## 单元测试
```php
 ./vendor/bin/co-phpunit tests
```

推荐使用mysql著名的employees样例库进行测试和学习mysql: https://github.com/datacharmer/test_db

## 注册链接信息

添加数据库的连接信息，用于创建链接。

```php
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\Db\Config;

$config = new Config();
$config->setDatabase('easyswoole_orm');
$config->setUser('root');
$config->setPassword('');
$config->setHost('127.0.0.1');

DbManager::getInstance()->addConnection(new Connection($config));
```

## 模型定义

定义一个模型基础的模型，只需要创建一个类，并且继承`EasySwoole\ORM\AbstractModel`即可

```php

<?php

namespace EasySwoole\ORM\Tests;

use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Utility\Schema\Table;

/**
 * 用于测试的用户模型
 * Class UserModel
 * @package EasySwoole\ORM\Tests
 */
class UserModel extends AbstractModel
{
}
```

## 定义表结构

在模型类中，强制我们实现一个`function schemaInfo(): Table`方法，要求返回一个`EasySwoole\ORM\Utility\Schema\Table`类

```php
class UserModel extends AbstractModel
{
    /**
     * 表的定义
     * 此处需要返回一个 EasySwoole\ORM\Utility\Schema\Table
     * @return Table
     */
    protected function schemaInfo(): Table
    {
        $table = new Table('siam');
        $table->colInt('id')->setIsPrimaryKey(true);
        $table->colChar('name', 255);
        $table->colInt('age');
        return $table;
    }
}

```

### 表字段

在Table中，有colX系列方法，用于表示表字段的类型，如以上示例的Int,Char

```php
$table->colInt('id')；
$table->colChar('name', 255);
```

### 表主键

如果需要将某个字段指定为主键 则用连贯操作方式，在后续继续指定即可。

```php
$table->colInt('id')->setIsPrimaryKey(true);
```

## 增

```php
<?php
$model = new UserModel([
    'name' => 'siam',
    'age'  => 21,
]);
// 不同设置值的方式
// $model->setAttr('id', 7);
// $model->id = 10003;

$res = $model->save();
var_dump($res); // 返回自增id 或者主键的值  失败则返回null
```

## 删

```php
<?php
// 删除(返回影响的记录数)
// 不同方式
$res = UserModel::create()->destroy(1);
$res = UserModel::create()->destroy('2,4,5');
$res = UserModel::create()->destroy([3, 7]);
$res = UserModel::create()->destroy(['age' => 21]);
$res = UserModel::create()->destroy(function (QueryBuilder $builder) {
    $builder->where('id', 1);
});

var_dump($res);
```

## 获取器和修改器注意

数据表的字段会自动转换为驼峰法

## 修改器

setter，修改器的作用是可以在数据赋值的时候自动进行转换处理，例如：

```php
class UserModel extends AbstractModel
{
    /**
     * $value mixed 是原值
     * $data  array 是当前model所有的值 
     */
    protected function setNameAttr($value, $data)
    {
        return $value."_加一个统一后缀";
    }
}
```
如下代码在设置保存的时候将会被修改内容
```php
$model = new UserModel([
    'name' => 'siam',
    'age'  => 21,
]);
$model->save();
```


## 改

```php
<?php

// 可以直接静态更新
$res = UserModel::create()->update([
    'name' => 'new'
], ['id' => 1]);

// 根据模型对象进行更新（无需写更新条件）
$model = UserModel::create()->get(1);

// 不同设置新字段值的方式
$res = $model->update([
    'name' => 123,
]);
$model->name = 323;
$model['name'] = 333;

// 调用保存  返回bool 成功失败
$res = $model->update();
var_dump($res);
```

## 查
```php
<?php

// 获取单条(返回一个模型)
$res = UserModel::create()->get(10001);
$res = UserModel::create()->get(['emp_no' => 10001]);
var_dump($res); // 如果查询不到则为null
// 不同获取字段方式
var_dump($res->emp_no);
var_dump($res['emp_no']);

// 批量获取 返回一个数组  每一个元素都是一个模型对象
$res = UserModel::create()->all([1, 3, 10003]);
$res = UserModel::create()->all('1, 3, 10003');
$res = UserModel::create()->all(['name' => 'siam']);
$res = UserModel::create()->all(function (QueryBuilder $builder) {
    $builder->where('name', 'siam');
});
var_dump($res);

```

### 复杂查询

在以上查的示例中，我们可以看到最后一个是闭包方式，我们可以在其中使用QueryBuilder的任意连贯操作，来构建一个复杂的查询操作。

支持方法列表查看Mysqli。

```php
$res = UserModel::create()->all(function (QueryBuilder $builder) {
    $builder->where('name', 'siam');
    $builder->order('id');
    $builder->limit(10);
});
```

## 获取器

getter，获取器的作用是在获取数据的字段值后自动进行处理

```php
class UserModel extends AbstractModel
{
    /**
     * $value mixed 是原值
     * $data  array 是当前model所有的值 
     */
    protected function getIdAttr($value, $data)
    {
        // id = 1 管理员
        if ($value == 1){
            return '管理员';
        }
        return '普通账号';
    }
    
    protected function getStatusAttr($value)
    {
        $status = [-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
        return $status[$value];
    }
}
```

获取器还可以定义数据表中不存在的字段，例如：
```php
protected function getEasyswooleAttr($value,$data)
{
  return 'Easyswoole用户-'.$data['id'];
}
```
那么在外部我们就可以使用这个easyswoole字段了
```php
$res = UserModel::create()->get(4);
var_dump($res->easyswoole);
```


## 事务

### 开启事务
传参说明

| 参数名          | 是否必须 | 参数说明                                                     |
| --------------- | -------- | ------------------------------------------------------------ |
| connectionNames | 否       | string或者array<br/>在addConnection时指定。一般情况下无需特别设置 |



返回说明：bool  开启成功则返回true，开启失败则返回false




```php
DbManager::getInstance()->startTransaction($connectionNames = 'default');
```

### 提交事务

传参说明

| 参数名      | 是否必须 | 参数说明                                                     |
| ----------- | -------- | ------------------------------------------------------------ |
| connectName | 否       | 指定提交一个连接名，若不传递，则自动提交当前协程内获取的事务连接。<br/>一般情况下无需特别设置 |



返回说明：bool  提交成功则返回true，失败则返回false

```php
DbManager::getInstance()->commit($connectName = null);
```

### 回滚事务

传参说明

| 参数名      | 是否必须 | 参数说明                                                     |
| ----------- | -------- | ------------------------------------------------------------ |
| connectName | 否       | 指定提交一个连接名，若不传递，则自动提交当前协程内获取的事务连接。<br/>一般情况下无需特别设置 |



返回说明：bool  提交成功则返回true，失败则返回false

```php
DbManager::getInstance()->rollback();
```



### 事务用例

```php 
$user = UserModel::create()->get(4);

$user->age = 4;
// 开启事务
$strat = DbManager::getInstance()->startTransaction();

// 更新操作
$res = $user->update();

// 不管更新成功还是失败，直接回滚
$rollback = DbManager::getInstance()->rollback();

// 返回false 因为连接已经回滚。事务关闭。
$commit = DbManager::getInstance()->commit();
var_dump($commit);
```

## 关联 - 一对一

在模型中定义方法

```php
public function setting()
{
    return $this->hasOne(UserSettingModel::class);
}

public function settingWhere()
{
    return $this->hasOne(UserSettingModel::class, function(QueryBuilder $query){
        $query->where('u_id', $this->id);
        $query->where('status', 1);
        return $query;
    });
}
```

UserSettingModel 也是一个Model类，只是定义了一个简单的表结构

使用
```php
$res = UserModel::create()->get(1);

/**
 * 关联 一对一
 */
var_dump($res);

var_dump($res->setting); 
var_dump($res->settingWhere); 
// 如果查询不到则为null  查询得到则为一个UserSettingModel类的实例 可以继续调用ORM的方式 快速更新 删除等
```



## 关联 - 一对多

在模型中定义方法

```php
public function orders()
{
    return $this->hasMany(OrdersModel::class, null, null, 'u_id');
}
```

OrdersModel 也是一个Model类，只是定义了一个简单的表结构

使用
```php
$res = UserModel::create()->get(1);

/**
 * 关联 一对多
 */
var_dump($res);
var_dump($res->orders); 
// 如果查询不到则为null  
// 查询得到则为一个数组，每一个子元素都是OrdersModel类的实例
```