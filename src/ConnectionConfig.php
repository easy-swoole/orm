<?php

namespace EasySwoole\ORM;

use EasySwoole\Spl\SplBean;

class ConnectionConfig extends SplBean
{
    /**
     * @var string|null
     */
    protected $name;

    protected $host;
    protected $user;
    protected $password;
    protected $database;
    protected $port = 3306;
    protected $timeout = 45;
    protected $charset = 'utf8';
    protected $autoPing = 5;

    //for pool
    protected $intervalCheckTime = 15*1000;
    protected $maxIdleTime = 10;
    protected $maxObjectNum = 20;
    protected $minObjectNum = 5;
    protected $getObjectTimeout = 3.0;
    protected $loadAverageTime = 0.001;
    protected $extraConf;



    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }


}