<?php


namespace EasySwoole\ORM\Db;


class Result
{
    private $lastInsertId;
    private $result;
    private $lastError;
    private $lastErrorNo;
    private $affectedRows;
    private $totalCount = 0;


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

    /**
     * @return mixed
     */
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    /**
     * @param mixed $affectedRows
     */
    public function setAffectedRows($affectedRows): void
    {
        $this->affectedRows = $affectedRows;
    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @param int $totalCount
     */
    public function setTotalCount(int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }

    public function toArray()
    {
        return [
            'lastInsertId' => $this->lastInsertId,
            'result'       => $this->result,
            'lastError'    => $this->lastError,
            'lastErrorNo'  => $this->lastErrorNo,
            'affectedRows' => $this->affectedRows,
            'totalCount'   => $this->totalCount
        ];
    }
}