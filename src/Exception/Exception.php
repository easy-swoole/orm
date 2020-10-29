<?php


namespace EasySwoole\ORM\Exception;


use EasySwoole\ORM\Db\Result;

class Exception extends \Exception
{
    /** @var Result */
    private $lastQueryResult;

    public function lastQueryResult(): ?Result
    {
        return $this->lastQueryResult;
    }

    public function setLastQueryResult(Result $result)
    {
        $this->lastQueryResult = $result;
    }
}