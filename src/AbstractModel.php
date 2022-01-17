<?php

namespace EasySwoole\ORM;

use EasySwoole\DDL\Blueprint\Create\Column;
use EasySwoole\DDL\Blueprint\Create\Table;
use EasySwoole\Mysqli\QueryBuilder;

abstract class AbstractModel
{
    /** @var RuntimeConfig */
    private $runtimeConfig;

    abstract function tableName():string;

    function runtimeConfig(?RuntimeConfig $config = null):RuntimeConfig
    {
        if($config == null){
            if($this->runtimeConfig == null){
                $this->runtimeConfig = new RuntimeConfig();
            }
        }else{
            $this->runtimeConfig = $config;
        }
        return $this->runtimeConfig;
    }

    function schemaInfo():Table
    {
        $key = md5(static::class.$this->tableName().$this->runtimeConfig()->getConnectionConfig()->getName());
        $item = RuntimeCache::getInstance()->get($key);
        if($item){
            return $item;
        }
        $client = $this->runtimeConfig->getClient();
        $query = new QueryBuilder();
        $query->raw("show full columns from {$this->tableName()}");

        $fields = DbManager::getInstance()
            ->__exec($client,$query,false,$this->runtimeConfig->getConnectionConfig()->getTimeout())
            ->getResult();
        $table = new Table($this->tableName());

        foreach ($fields as $field){
            //创建字段与类型处理
            $columnTypeArr = explode(' ',$field['Type']);
            $tmpIndex = strpos($columnTypeArr[0],'(');
            //例如  varchar(20)
            if($tmpIndex !== false){
                $type = substr($columnTypeArr[0],0,$tmpIndex);
                $limit = substr($columnTypeArr[0],$tmpIndex+1,strpos($columnTypeArr[0],')')-$tmpIndex-1);
                $columnObj = new Column($field['Field'],$type);
                $limitArr = explode(',',$limit);
                if (isset($limitArr[1])){
                    $columnObj->setColumnLimit($limitArr);
                }else{
                    $columnObj->setColumnLimit($limitArr[0]);
                }
            }else{
                $type = $columnTypeArr[0];
                $columnObj = new Column($field['Field'],$type);
            }
            if (in_array('unsigned',$columnTypeArr)){
                $columnObj->setIsUnsigned();
            }
            if ($field['Key']=='PRI'){
                $columnObj->setIsPrimaryKey();
            }
            //默认值
            if ($field['Default']!==null){
                $columnObj->setDefaultValue($field['Default']);
            }else{
                $columnObj->setDefaultValue(null);
            }
            if ($field['Extra']=='auto_increment'){
                $columnObj->setIsAutoIncrement();
            }
            if (!empty($field['Comment'])){
                $columnObj->setColumnComment($field['Comment']);
            }
            $table->addColumn($columnObj);
        }

        RuntimeCache::getInstance()->set($key,$table);

        return $table;
    }



}