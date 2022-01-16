<?php

namespace EasySwoole\ORM;

abstract class AbstractModel
{
    /** @var RuntimeConfig */
    private $runtimeConfig;

    function runtimeConfig(?RuntimeConfig $config = null):RuntimeConfig
    {
        if($config == null){
            if($this->runtimeConfig == null){
                $this->runtimeConfig = new RuntimeConfig();
            }
        }else{
            $this->runtimeConfig = $config;
        }
        return $this->runtimeConfig;
    }
}