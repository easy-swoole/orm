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
     * @return array
     */
    public function getResultOne(): ?array
    {
        return $this->result[0] ?? null;
    }

    /**
     * @param string $column
     * @return array
     */
    public function getResultColumn(?string $column = null): ?array
    {
        if (is_array($this->result)) {
            if (is_string($column) && isset($this->result[0][$column])) {
                return array_column($this->result, $column);
            }
            if (!isset($this->result[0]) || $this->result[0] === null){
                return null;
            }
            return array_column($this->result, key($this->result[0]));
        }

        return null;
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function getResultScalar(?string $column = null)
    {
        $result = $this->getResultColumn($column);
        if (is_array($result) && count($result) > 0) {
            return reset($result);
        }

        return null;
    }

    /**
     * @param string $column
     * @return array
     */
    public function getResultIndexBy(string $column): ?array
    {
        if (is_array($this->result) &&
            isset($this->result[0][$column])) {
            $indexedModels = [];
            foreach ($this->result as $model) {
                $indexedModels[$model[$column]] = $model;
            }
            return $indexedModels;
        }

        return null;
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