Easyswoole-ORM

## 项目背景

由于swoole协程环境不可以直接使用php-fpm的orm组件（由于存在静态全局变量、连接层没有做好协程处理，无法协程安全地使用）

所以easyswoole花费大量时间精力维护orm组件，连贯操作等功能设计借鉴TP5.0的ORM组件。

有疑问、功能建议、bug反馈请在QQ群、github issue、直接联系宣言提交。

## 问题反馈模板

一个完整的提问需要包含以下几点：

- 1.出现了问题，怀疑orm组件的bug，需要先行编写最小测试。（比如一个新的类，单独只调用一个功能，都出问题了，排除其他因素影响）
- 2.用文字描述出现的问题，附带运行和调试的参数截图
- 3.附带第一步最小测试复现脚本

## 安装

```
composer require easyswoole/orm
```

## RFC

Model Invoke

### 查询

#### 1.1、获取单个数据

```php
// 主键条件
Model::invoke()->get(1);
=> select * from table where primary_key = 1
    
// 数组条件
Model::invoke()->get([col => val]);
// 闭包条件查询
Model::invoke()->get(function ($query) {
    $query->where(col, val);
    // Or $query->where([col => val]);
});
Model::invoke()->where(col, val)->get();
Model::invoke()->where(col, val, '=')->get();
Model::invoke()->where(col, val)->find();
=> select * from table where col = val limit 1
```

#### 1.2、获取多个数据

```php
// 主键条件
Model::invoke()->all('1,2,3');
// 主键条件数组
Model::invoke()->all([1,2,3]);
=> select * from table where primary_key in (1,2,3)
    
// 数组条件
Model::invoke()->all([col => val]);
=> select * from table where col = val

// 闭包条件查询
Model::invoke()->all(function ($query) {
    $query->where(col, val)->limit(num)->order(col, 'asc')
});
// 链式调用
Model::invoke()->where(col, val)
    ->limit(num)
    ->order(col, 'asc')
    ->select();
=> SELECT * FROM table WHERE col = val ORDER BY col ASC LIMIT num
```

#### 1.3、获取某个字段或者某个列的值

```php
// 获取某行某个字段的值
Model::invoke()->where(col, val)->value(col)
=> SELECT col FROM table WHERE col = val LIMIT 1

// 获取某个列的值
Model::invoke()->where(col, val)->value(col)
=> SELECT col FROM table WHERE col = val
    
// 获取多个列的值    
Model::invoke()->where(col, val)->value(col1, col2)
Model::invoke()->where(col, val)->value('col1,col2')
=> SELECT col1,col2 FROM table WHERE col = val
```

#### 1.4、数据分批处理

```php
Model::invoke()->chunk(3, function ($objs) {
     
});
=> SELECT * FROM table WHERE primary_key > 1 ORDER BY primary_key ASC LIMIT 1
=> SELECT * FROM table WHERE primary_key > 2 ORDER BY primary_key ASC LIMIT 1
=> SELECT * FROM table WHERE primary_key > 3 ORDER BY primary_key ASC LIMIT 1
```

### 新增

#### 1.5、添加1条数据

```php
# save 方法新增数据返回写入的记录数

$model = Model::invoke();
$model->col = val;
$model->save();
=> INSERT INTO table (col) VALUES (val)
    
// 批量赋值
$model = Model::invoke([
    col1 => val1,
    col2 => val2
]);
$model->save();    
// 或者
$model = Model::invoke();
$model->data([
    col1 => val1,
    col2 => val2
]);
$model->save();
=> INSERT INTO table (col1, col2) VALUES (val1, val2)
    
// 只保存指定字段
$model = Model::invoke([
    col1 => val1,
    col2 => val2
]);
$model->allowField([col1])->save();
=> INSERT INTO table (col1) VALUES (val1)
```

#### 1.6、获取自增id

```php
$model = Model::invoke();
$model->col = val;
$model->save();
// 获取自增id
echo $model->primary_key;
=> INSERT INTO table (col) VALUES (val)
```

#### 1.7、添加多条数据

```php
// 默认为新增操作
// - 当保存的数据中包含主键时则自动识别为更新操作
$model = Model::invoke();
$model->saveAll([
    [col1 => val1, col2 => val2],
    [col1 => val3, col2 => val4],
]);
=> INSERT INTO table(col1, col2) VALUES (val1, val2);
=> INSERT INTO table(col1, col2) VALUES (val3, val4);

// 更新（saveAll的数据中包含 primary_key）
$model = Model::invoke();
$model->saveAll([
    [primary_key => val11, col1 => val1, col2 => val2],
    [primary_key => val22, col1 => val3, col2 => val4],
]);
=> UPDATE table SET col1 = val1, col2 = val2 WHERE primary_key = val11;
=> UPDATE table SET col1 = val3, col2 = val4 WHERE primary_key = val22;

// 强制批量新增
$model = Model::invoke();
$model->saveAll([
    [primary_key => val11, col1 => val1, col2 => val2],
    [primary_key => val22, col2 => val3, col2 => val4],
], false);
=> INSERT INTO table(primary_key, col1, col2) VALUES (val11, val1, val2);
=> INSERT INTO table(primary_key, col1, col2) VALUES (val22, val3, val4);


// 使用静态方法 create 方法新增
// 返回当前模型的对象实例
$model = Model::create([
    col1 => val1,
    col2 => val2
]);
=> INSERT INTO table (col1, col2) VALUES (val1, val2)
```

### 更新

#### 1.8、查找并更新

```php
$model = Model::invoke()->get(1);
$model->col = val;
$model->save();
=> SELECT * FROM table WHERE primary_key = 1 LIMIT 1
=> UPDATE table SET col = val  WHERE primary_key = 1
```

#### 1.9、直接更新数据

```php
$model = Model::invoke();
$model->save([
    col => val
], [where_col => where_val]);
=> UPDATE table SET col = val  WHERE where_col = where_val
    
// 只更新指定字段
$model = Model::invoke();
$model->allowField(['col'])->save([
    col => val,
    col1 => val1
], [where_col => where_val]);
=> UPDATE table SET col = val  WHERE where_col = where_val
```

#### 1.10、批量更新数据

```php
上述 saveAll 保存的数据包括主键即可。
```

> 批量更新仅能根据主键值进行更新，其它情况请使用`foreach`遍历更新。

```php
// 显式指定
// 强制根据主键条件批量更新数据
$model = Model::invoke();
$model->isUpdate()->saveAll([
    [primary_key => val11, col1 => val1, col2 => val2],
    [primary_key => val22, col1 => val3, col2 => val4],
]);
=> UPDATE table SET col1 = val1, col2 = val2 WHERE primary_key = val11;
=> UPDATE table SET col1 = val3, col2 = val4 WHERE primary_key = val22;
```

#### 1.11、使用 update 方法进行更新

```php
// 使用 where 条件更新
$model = Model::invoke();
$model->where(col, val)->update([col1 => val1]);
=> UPDATE table SET col1 = val1 WHERE col = val;

// 使用主键条件更新
// 更新的数据包含主键列时 无需使用 where 方法
$model = Model::invoke();
$model->update([primary_key => val11, col => val]);
=> UPDATE table SET col = val WHERE primary_key = val11;
```

#### 1.12、闭包方法更新

```php
$model = Model::invoke();
$model->save([col2 => val2], function ($query) {
    $query->where(col, val)->where(col1, val1, '>');
});
=> UPDATE table SET col2 = val2 WHERE (col = val AND col1 > val1)
```

#### 1.13、自动识别

```php
// 显式指定更新数据
$model = Model::invoke();
$model->isUpdate(true)
    ->save([primary_key => 1, col => val]);
=> UPDATE table SET col = val WHERE primary_key = 1
```

### 删除

#### 1.14、删除当前模型

```php
$model = Model::invoke()->get(1);
$model->delete();
=> DELETE FROM table WHERE primary_key = 1
```

#### 1.15、根据主键删除

```php
$model = Model::invoke();
$model->destroy(1);
=> DELETE FROM table WHERE primary_key = 1

$model = Model::invoke();
$model->destroy('1,2,3');
$model->destroy([1,2,3]);
=> DELETE FROM table WHERE primary_key = 1;
=> DELETE FROM table WHERE primary_key = 2;
=> DELETE FROM table WHERE primary_key = 3;
```

#### 1.16、条件删除

```php
$model = Model::invoke();
$model->destroy([col => val]);
=> DELETE FROM table WHERE col = val;

// 闭包删除
$model = Model::invoke();
$model->destroy(function ($query) {
    $query->where(col, val, '>');
});
=> SELECT * FROM table WHERE col > val
=> DELETE FROM table WHERE primary_key = key1
=> DELETE FROM table WHERE primary_key = key2
=> DELETE FROM table WHERE primary_key = key3
...
=> DELETE FROM table WHERE primary_key = keyX
// keyX 取决于最前面查询返回的所有记录的 主键id
    
// 根据 where 查询条件删除
$model = Model::invoke();
$model->where(col, val, '>')->delete();
=> DELETE FROM table WHERE col > val;
```

### 聚合

#### 1.17、count

```php
$model = Model::invoke();
$model->count();
=> SELECT COUNT(*) AS es_count FROM table LIMIT 1
    
$model = Model::invoke();
$model->count(col);
=> SELECT COUNT(col) AS es_count FROM table LIMIT 1    

$model = Model::invoke();
$model->where(col, val, '>')->count();
=> SELECT COUNT(*) AS es_count FROM table WHERE col > val LIMIT 1
```

#### 1.18、max

```php
$model = Model::invoke();
$model->max('age');
=> SELECT MAX(col) AS es_max FROM table LIMIT 1
```

#### 1.19、min

```php
$model = Model::invoke();
$model->min(col);
=> SELECT MIN(col) AS es_min FROM table LIMIT 1
```

#### 1.20、avg

```php
$model = Model::invoke();
$mode->where(col, val)->avg(col);
=> SELECT AVG(col) AS es_avg FROM table WHERE col = val LIMIT 1
```

#### 1.21、sum

```php
$model = Model::invoke();
$model->sum(col);
=> SELECT SUM(col) AS es_sum FROM table WHERE col = val  LIMIT 1

$model = Model::invoke();
$mode->where(col, val)->SUM(col);
=> SELECT SUM(col) AS es_sum FROM table WHERE col = val  LIMIT 1
```

## 官网文档

http://www.easyswoole.com/Cn/Components/Orm/changeLog.html

## 单元测试

```php
 ./vendor/bin/co-phpunit tests
```

推荐使用mysql著名的employees样例库进行测试和学习mysql: https://github.com/datacharmer/test_db

## 主要项目负责人

- 宣言(Siam) 59419979@qq.com

## 参与贡献方式

- 有实际生产使用的，提出升级迭代建议
- 使用过程中遇到问题，并且查看文档，基本排除个人原因导致的问题，怀疑bug，及时反馈
- 参与orm组件的代码维护、功能升级
- 参与orm组件的文档维护（也就是加入easyswoole文档维护团队）

## 开源协议

Apache-2.0

## 功能介绍

- 基于easyswoole/mysqli组件，承当构造层职责。
- 基于easyswoole/pool组件，承当基础连接池。
- 支持执行自定义sql语句、构造器查询。
- 支持事务，DbManager连接管理器也可承当事务管理器的职责。
- 支持关联查询。
- 支持多数据库配置，读写分离。
- 便捷的连贯操作、聚合操作。

## 设计层级

![设计层级](http://www.easyswoole.com/Images/Orm/%E8%AE%BE%E8%AE%A1%E5%B1%82%E7%BA%A7.svg)