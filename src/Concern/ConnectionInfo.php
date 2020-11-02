<?php

namespace EasySwoole\ORM\Concern;

use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Db\ClientInterface;

trait ConnectionInfo
{
    /** @var string 连接池名称 */
    protected $connectionName = 'default';
    /** @var null|string 临时连接名 */
    private $tempConnectionName = null;


    /**@var ClientInterface */
    private $client;

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

    /**
     * 设置执行client
     * @param ClientInterface|null $client
     * @return $this
     */
    public function setExecClient(?ClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * 获取invoke注入的客户端
     * @return ClientInterface|null
     */
    public function getExecClient()
    {
        if ($this->client){
            return $this->client;
        }
        return null;
    }

    /**
     * 获取当前模型的执行链接
     * @return ClientInterface|null|string
     */
    public function getQueryConnection()
    {
        if ($this->client instanceof ClientInterface){
            return $this->client;
        }
        return $this->getConnectionName();
    }
}