<?php

namespace EasySwoole\ORM\Db;

use EasySwoole\Mysqli\Config;
use EasySwoole\ORM\ConnectionConfig;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\AbstractPool;


class Pool extends AbstractPool
{

    protected function createObject()
    {
        $mysqlConfig = new Config($this->getConfig()->toArray());
        $client = new MysqlClient();
        if($client->connect($mysqlConfig->toArray())){
            $client->__lastPingTime = 0;
            $client->setConnectionConfig(new ConnectionConfig($this->getConfig()->toArray()));
            return $client;
        }else{
            throw new Exception("mysql client connect to {$mysqlConfig->getHost()}:{$mysqlConfig->getPort()} error: {$client->connect_error}");
        }
    }

    protected function itemIntervalCheck($item): bool
    {
        /** @var ConnectionConfig $config */
        $config = $this->getConfig();

        /**
         *  auto ping是为了保证在 idleMaxTime周期内的可用性 （如果超出了周期还没使用，则代表现在进程空闲，可以先回收）
         */
        if($config->getAutoPing() > 0 && (time() - $item->__lastPingTime > $config->getAutoPing())){
            try{
                //执行一个sql触发活跃信息
                $item->rawQuery('select 1');
                // 标记最后一次ping的时间  不修改__lastUseTime是为了让idleCheck 在空闲的时候正常回收
                $item->__lastPingTime = time();
                return true;
            }catch (\Throwable $throwable){
                //异常说明该链接出错了，return 进行回收
                return false;
            }
        }else{
            return true;
        }
    }

    /**
     * @param int|null $num
     * @return int
     * 屏蔽在定时周期检查的时候，出现连接创建出错，导致进程退出。
     */
    public function keepMin(?int $num = null): int
    {
        try{
            return parent::keepMin($num);
        }catch (\Throwable $throwable){
            return $this->status(true)['created'];
        }
    }
}