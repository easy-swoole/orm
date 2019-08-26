<?php


namespace EasySwoole\TpORM\Driver;


class Result
{
    private $lastInsertId;
    private $data;
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
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }
}