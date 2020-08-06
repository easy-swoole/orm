<?php

namespace EasySwoole\ORM\Utility\Schema;

/**
 * 数据表结构
 * Class Table
 * @package EasySwoole\ORM\Utility\Schema
 */
class Table extends \EasySwoole\DDL\Blueprint\Table
{

    /**
     * 注入自定义的column
     * addColumn
     * @param \EasySwoole\DDL\Blueprint\Column $column
     * @author Tioncico
     * Time: 15:01
     */
    function addColumn(\EasySwoole\DDL\Blueprint\Column $column){
        $this->columns[$column->getColumnName()] = $column;
    }

    /**
     * 返回自定义的Column
     * 以便扩展该类的处理方法
     * @param string $columnName
     * @param string $columnType
     * @return \EasySwoole\DDL\Blueprint\Column|Column
     */
    function createColumn(string $columnName, string $columnType)
    {
        return new Column($columnName, $columnType);
    }

    /**
     * 返回自定义的Index
     * 以便扩展该类的处理方法
     * @param string|null $indexName
     * @param $indexType
     * @param $indexColumns
     * @return \EasySwoole\DDL\Blueprint\Index
     */
    function createIndex(?string $indexName, $indexType, $indexColumns)
    {
        return parent::createIndex($indexName, $indexType, $indexColumns);
    }

    /**
     * Table Getter
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Comment Getter
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Engine Getter
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Charset Getter
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Columns Getter
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Indexes Getter
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * isTemporary Getter
     * @return bool
     */
    public function isTemporary(): bool
    {
        return $this->isTemporary;
    }

    /**
     * IfNotExists Getter
     * @return bool
     */
    public function isIfNotExists(): bool
    {
        return $this->ifNotExists;
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
     * 当前表的索引字段
     * @return mixed|null
     */
    public function getPkFiledName()
    {
        // 首先查找是否有PrimaryKey索引
        $return = [];
        foreach ($this->indexes as $indexName => $index) {
            if ($index instanceof Index && $index->getIndexType() === \EasySwoole\DDL\Enum\Index::PRIMARY) {
                $return[] =  $index->getIndexName();
            }
        }

        if (!empty($return)) return $return;

        // 然后查找每个字段是否设置了Primary属性
        foreach ($this->columns as $columnName => $column) {
            if ($column instanceof Column && $column->getIsPrimaryKey()) {
                $return[] =  $column->getColumnName();
            }
        }

        if (!empty($return)){
            if (count($return) === 1) return $return[0];
            return $return;
        }

        return null;
    }

    /**
     * 获取自增字段名  mysql规定只有一个
     * @return mixed|null
     */
    public function getAutoIncrementFiledName()
    {
        foreach ($this->columns as $columnName => $column) {
            if ($column instanceof Column && $column->getAutoIncrement()) {
                return $column->getColumnName();
            }
        }
        return null;
    }
}