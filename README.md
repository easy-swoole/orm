Easyswoole-ORM

## 单元测试
```php
 ./vendor/bin/co-phpunit tests
```

推荐使用mysql著名的employees样例库进行测试和学习mysql: https://github.com/datacharmer/test_db

## 功能介绍

- 基于easyswoole/mysqli组件，承当构造层职责。
- 基于easyswoole/pool组件，承当基础连接池。
- 支持执行自定义sql语句、构造器查询。
- 支持事务，DbManager连接管理器也可承当事务管理器的职责。
- 支持关联查询。
- 支持多数据库配置，读写分离。
- 便捷的连贯操作、聚合操作。

## 设计层级

op1=>operation: My Ope
op2=>operation: My Ope
op3=>operation: My Ope

op1(right)->op2(right)->op3