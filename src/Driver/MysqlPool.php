<?php

namespace EasySwoole\ORM\Driver;

use EasySwoole\Component\Pool\AbstractPool;

/**
 * Class MysqlPool
 * @package EasySwoole\ORM\Driver
 */
class MysqlPool extends AbstractPool
{

    /**
     * Create Pool Object
     * @return MysqlObject|null
     */
    protected function createObject()
    {
        $client = new MysqlObject();
        if ($client->connect($this->getConfig()->toArray())) {
            return $client;
        } else {
            return null;
        }
    }
}