# Usage
# 注册一个连接
```php
use EasySwoole\ORM\ConnectionConfig;
use EasySwoole\ORM\DbManager;

$config = [
    'host'     => '',
    'port'     => ,
    'user'     => '',
    'password' => '',
    'database' => ''
];
$con = new ConnectionConfig($config);
DbManager::getInstance()->addConnection($con);

```

# 主进程无协程环境使用（CLI）
```php
use EasySwoole\ORM\DbManager;

DbManager::getInstance()->runInMainProcess(function (DbManager $dbManager){
    $ret = $dbManager->fastQuery()->raw("select version()");
    var_dump($ret);
});
```