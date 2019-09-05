<?php


namespace EasySwoole\ORM\Tests;


use EasySwoole\ORM\AbstractModel;

class UserModel extends AbstractModel
{

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
}