<?php

namespace EasySwoole\ORM\Utility\Schema;

/**
 * 索引结构
 * Class Index
 * @package EasySwoole\ORM\Utility\Schema
 */
class Index extends \EasySwoole\DDL\Blueprint\Index
{
    /**
     * IndexName Getter
     * @return mixed
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * IndexType Getter
     * @return mixed
     */
    public function getIndexType()
    {
        return $this->indexType;
    }

    /**
     * IndexColumns Getter
     * @return mixed
     */
    public function getIndexColumns()
    {
        return $this->indexColumns;
    }

    /**
     * IndexComment Getter
     * @return mixed
     */
    public function getIndexComment()
    {
        return $this->indexComment;
    }
}