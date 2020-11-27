<?php

namespace EasySwoole\ORM\Tests\models;


use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;

class CastsModel extends AbstractModel
{
    protected $tableName = 'casts';

    protected $autoTimeStamp = false;

    protected $casts = [
        'int_value' => 'int',
        'float_value' => 'float',
        'json_value' => 'json',
        'array_value' => 'array',
        'date_value' => 'date',
        'datetime_value' => 'datetime',
        'timestamp_value' => 'timestamp'
    ];

    public function __construct(array $data = [])
    {
        $table = new Table('casts');
        $table->setIfNotExists();
        $table->int('id')->setIsPrimaryKey(true)->setIsAutoIncrement(true);
        $table->int('int_value', 11);
        $table->float('float_value', 10, 2);
        $table->char('json_value', 100);
        $table->char('array_value', 100);
        $table->date('date_value');
        $table->datetime('datetime_value');
        $table->int('timestamp_value', 11);

        $builder = new QueryBuilder();
        $builder->raw($table->__toString());

        DbManager::getInstance()->query($builder);

        parent::__construct($data);
    }
}