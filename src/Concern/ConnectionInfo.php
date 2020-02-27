<?php

namespace EasySwoole\ORM\Concern;

use EasySwoole\ORM\AbstractModel;

trait ConnectionInfo
{
    /** @var string 连接池名称 */
    protected $connectionName = 'default';
    /** @var null|string 临时连接名 */
    private $tempConnectionName = null;


    /**
     * 连接名设置
     * @param string $name
     * @param bool $isTemp
     * @return ConnectionInfo|AbstractModel
     */
    function connection(string $name, bool $isTemp = false)
    {
        if ($isTemp) {
            $this->tempConnectionName = $name;
        } else {
            $this->connectionName = $name;
        }
        return $this;
    }


    /**
     * 获取使用的链接池名
     * @return string|null
     */
    public function getConnectionName()
    {
        if ($this->tempConnectionName) {
            $connectionName = $this->tempConnectionName;
        } else {
            $connectionName = $this->connectionName;
        }
        return $connectionName;
    }
}