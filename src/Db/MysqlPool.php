<?php


namespace EasySwoole\ORM\Db;


use EasySwoole\Mysqli\Config as MysqlConfig;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\AbstractPool;

class MysqlPool extends AbstractPool
{
    protected function createObject()
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $client = new MysqliClient(new MysqlConfig($config->toArray()));
        if($client->connect()){
            $client->__lastPingTime = 0;
            return $client;
        }else{
            throw new Exception($client->mysqlClient()->connect_error);
        }
    }

    /**
     * @param MysqliClient $item
     * @return bool
     */
    public function itemIntervalCheck($item): bool
    {
        /**
         * 已经达到ping的时间间隔
         */
        /** @var Config $config */
        $config = $this->getConfig();
        if($config->getAutoPing() > 0 && (time() - $item->__lastPingTime > $config->getAutoPing())){
            try{
                //执行一个sql触发活跃信息
                $item->rawQuery('select 1');
                // 标记最后一次ping的时间  不修改__lastUseTime是为了让idleCheck 在空闲的时候正常回收
                // auto ping是为了保证在 idleMaxTime周期内的可用性 （如果超出了周期还没使用，则代表现在进程空闲，可以先回收）
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
            return $this->status()['created'];
        }
    }
}