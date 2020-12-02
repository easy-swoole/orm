<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


namespace EasySwoole\ORM\Tests\models;


use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;

class DuplicateModel extends AbstractModel
{
    protected $tableName = 'duplicate';

    protected $autoTimeStamp = false;

    public function __construct(array $data = [])
    {
        $table = new Table($this->tableName);
        $table->setIfNotExists();
        $table->int('id');
        $table->int('id1');
        $table->char('nickname', 30);
        $table->char('nickname1', 30);
        $table->primary('id', ['id', 'id1']);

        $builder = new QueryBuilder();
        $builder->raw($table->__toString());

        DbManager::getInstance()->query($builder);

        parent::__construct($data);
    }
}