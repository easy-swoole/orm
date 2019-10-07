<?php

namespace EasySwoole\ORM\Utility\Schema;

/**
 * 字段结构
 * Class Column
 * @package EasySwoole\ORM\Utility\Schema
 */
class Column extends \EasySwoole\DDL\Blueprint\Column
{
    /**
     * ColumnName Getter
     * @return mixed
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * ColumnType Getter
     * @return mixed
     */
    public function getColumnType()
    {
        return $this->columnType;
    }

    /**
     * ColumnLimit Getter
     * @return mixed
     */
    public function getColumnLimit()
    {
        return $this->columnLimit;
    }

    /**
     * ColumnComment Getter
     * @return mixed
     */
    public function getColumnComment()
    {
        return $this->columnComment;
    }

    /**
     * ColumnCharset Getter
     * @return mixed
     */
    public function getColumnCharset()
    {
        return $this->columnCharset;
    }

    /**
     * IsBinary Getter
     * @return mixed
     */
    public function getIsBinary()
    {
        return $this->isBinary;
    }

    /**
     * IsUnique Getter
     * @return mixed
     */
    public function getIsUnique()
    {
        return $this->isUnique;
    }

    /**
     * Unsigned Getter
     * @return mixed
     */
    public function getUnsigned()
    {
        return $this->unsigned;
    }

    /**
     * ZeroFill Getter
     * @return mixed
     */
    public function getZeroFill()
    {
        return $this->zeroFill;
    }

    /**
     * DefaultValue Getter
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * isNotNull Getter
     * @return bool
     */
    public function isNotNull(): bool
    {
        return $this->isNotNull;
    }

    /**
     * AutoIncrement Getter
     * @return mixed
     */
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * IsPrimaryKey Getter
     * @return mixed
     */
    public function getIsPrimaryKey()
    {
        return $this->isPrimaryKey;
    }
}