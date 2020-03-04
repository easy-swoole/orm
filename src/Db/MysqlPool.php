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
        /*
         * 如果最后一次使用时间超过autoPing间隔
         */
        /** @var Config $config */
        $config = $this->getConfig();
        if($config->getAutoPing() > 0 && (time() - $item->__lastUseTime > $config->getAutoPing())){
            try{
                //执行一个sql触发活跃信息
                $item->rawQuery('select 1');
                //标记使用时间，避免被再次gc
                $item->__lastUseTime = time();
                return true;
            }catch (\Throwable $throwable){
                //异常说明该链接出错了，return 进行回收
                return false;
            }
        }else{
            return true;
        }
    }
}