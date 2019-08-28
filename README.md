# Easyswoole-ORM

## 单元测试
```php
 ./vendor/bin/co-phpunit tests
```

## 基础使用
```
use EasySwoole\ORM\Driver\MysqlConfig;
use EasySwoole\ORM\Driver\Column;
use EasySwoole\ORM\Characteristic\Attribute;
use EasySwoole\ORM\Driver\MysqlDriver;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\AbstractModel;

/*
 * 初始化链接配置
 */

$conf = new MysqlConfig([
    'host'          => '',
    'port'          => 3300,
    'user'          => '',
    'password'      => '',
    'database'      => '',
    'timeout'       => 5,
    'charset'       => 'utf8mb4',
]);

$driver = new MysqlDriver($conf);

DbManager::getInstance()->addConnection($driver);

class User extends AbstractModel
{
    use Attribute;

    protected $schemaInfo = [
        'userId'=>Column::TYPE_INT,
        'userName'=>Column::TYPE_STRING
    ];
    protected function table(): string
    {
        return  'user_list';
    }

}

go(function (){
    $model = User::create();
    var_dump($model->get());
    var_dump($model->getQueryResult());
});
\Swoole\Timer::clearAll();

```