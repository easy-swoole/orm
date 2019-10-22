<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2019/10/22 0022
 * Time: 10:00
 */

namespace EasySwoole\ORM\Utility;

use EasySwoole\ORM\Utility\Schema\Column;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\ConnectionInterface;
use EasySwoole\ORM\Utility\Schema\Table;

/**
 * 根据数据表信息生成table对象
 * Class getTableObject
 * @package EasySwoole\ORM\Utility
 */
class TableObjectGeneration
{
    protected $tableName;
    protected $connection;
    protected $tableColumns;

    public function __construct(ConnectionInterface $connection, $tableName)
    {
        $this->tableName = $tableName;
        $this->connection = $connection;

    }

    public function getTableColumnsInfo()
    {
        $query = new QueryBuilder();
        $query->raw("show full columns from {$this->tableName}");
        $data = $this->connection->query($query);
        $this->tableColumns = $data->getResult();
        return $data->getResult();
    }

    public function generationTable(){
        $this->getTableColumnsInfo();
        $columns = $this->tableColumns;
        $table = new Table($this->tableName);
        foreach ($columns as $column){
            //新增字段对象
            $columnObj = $this->getTableColumn($column);
            $table->addColumn($columnObj);
        }
        return $table;
    }

    protected function getTableColumn($column):Column
    {
        $tmpIndex = strpos($column['Type'],'(');
        if($tmpIndex!==false){
            $type = substr($column['Type'],0,$tmpIndex);
            $limit = substr($column['Type'],$tmpIndex+1,-1);
        }else{
            $type = $column['Type'];
            $limit = null;
        }
        $columnObj = new Column($column['Field'],$type);

        //长度限制
        if ($limit!==null){
            $columnObj->setColumnLimit($limit);
        }
        //是否为主键
        if ($column['Key']=='PRI'){
            $columnObj->setIsPrimaryKey();
        }
        //默认值
        if ($column['Default']!==null){
            $columnObj->setDefaultValue($column['Default']);
        }
        if ($column['Extra']=='auto_increment'){
            $columnObj->setIsAutoIncrement();
        }
        if (!empty($column['Comment'])){
            $columnObj->setColumnComment($column['Comment']);
        }
        return $columnObj;
    }

}