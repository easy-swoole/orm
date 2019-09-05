<?php


namespace EasySwoole\ORM\Tests;


use EasySwoole\ORM\AbstractModel;

class UserModel extends AbstractModel
{

    /*
    * 用于测试
    */
    public $lastQuery;

    protected $pk = 'userId';

    protected function schemaInfo(): array
    {
        return [
            'userId'=>self::TYPE_INT,
            'userName'=>self::TYPE_STRING,
            'userAccount'=>self::TYPE_STRING
        ];
    }

    protected function table(): string
    {
        return 'user_list';
    }

    /*
     * 用于测试
     */
    protected function query(string $sql, array $bindParams = [])
    {
        $this->lastQuery = $this->queryBuilder()->getLastQuery();
        return null;
    }
}