<?php


namespace EasySwoole\ORM\Driver;


class Result
{
    private $lastInsertId;
    private $result;
    private $lastError;
    private $lastErrorNo;

    /**
     * @return mixed
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * @param mixed $lastInsertId
     */
    public function setLastInsertId($lastInsertId): void
    {
        $this->lastInsertId = $lastInsertId;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param mixed $lastError
     */
    public function setLastError($lastError): void
    {
        $this->lastError = $lastError;
    }

    /**
     * @return mixed
     */
    public function getLastErrorNo()
    {
        return $this->lastErrorNo;
    }

    /**
     * @param mixed $lastErrorNo
     */
    public function setLastErrorNo($lastErrorNo): void
    {
        $this->lastErrorNo = $lastErrorNo;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result): void
    {
        $this->result = $result;
    }

    public function toArray()
    {
        return [
            'lastInsertId'=>$this->lastInsertId,
            'result'=>$this->result,
            'lastError'=>$this->lastError,
            'lastErrorNo'=>$this->lastErrorNo
        ];
    }
}