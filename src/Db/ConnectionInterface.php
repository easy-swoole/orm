<?php


namespace EasySwoole\ORM\Db;


interface ConnectionInterface
{
    function defer(float $timeout = null):?ClientInterface;
    function invoke(callable $callable,float $timeout = null);
}