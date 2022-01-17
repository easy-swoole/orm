<?php

namespace EasySwoole\ORM\Exception;

use EasySwoole\Mysqli\QueryBuilder;

class Exception extends \Exception
{
    /** @var QueryBuilder|null */
    private $queryBuilder;

    /** @var string|null */
    private $sql;

    /**
     * @return QueryBuilder|null
     */
    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @param QueryBuilder|null $queryBuilder
     */
    public function setQueryBuilder(?QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return string|null
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * @param string|null $sql
     */
    public function setSql(?string $sql): void
    {
        $this->sql = $sql;
    }
}