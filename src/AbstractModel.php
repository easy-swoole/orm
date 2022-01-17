<?php

namespace EasySwoole\ORM;

use EasySwoole\DDL\Blueprint\Create\Column;
use EasySwoole\DDL\Blueprint\Create\Table;
use EasySwoole\DDL\Enum\DataType;
use EasySwoole\Mysqli\QueryBuilder;


abstract class AbstractModel implements \ArrayAccess
{
    /** @var RuntimeConfig */
    private $runtimeConfig;
    /** @var null|array */
    private $__data = null;
    private $__joinData = [];

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
        $key = $this->__modelhash();
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

    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getAttr($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->setAttr($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->setAttr($offset, null);
    }


    function __set($name, $value)
    {
        //访问的时候，恢复ddl定义的默认值
        if($this->__data === null){
            $this->__data = $this->__tableArray();
        }
        $this->setAttr($name, $value);
    }

    function __get($name)
    {
        //访问的时候，恢复ddl定义的默认值
        if($this->__data === null){
            $this->__data = $this->__tableArray();
        }
        return $this->getAttr($name);
    }

    public function __isset($name)
    {
        return ($this->getAttr($name) !== null);
    }

    public function getAttr($attrName)
    {
        $method = 'get' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
        if (method_exists($this, $method)) {
            return $this->$method($this->data[$attrName] ?? null, $this->data);
        }
        // 判断是否有关联查询
        if (method_exists($this, $attrName)) {
            return $this->$attrName();
        }
        // 是否是附加字段
        if (isset($this->_joinData[$attrName])){
            return $this->_joinData[$attrName];
        }
        return $this->data[$attrName] ?? null;
    }

    public function setAttr($attrName, $attrValue, $setter = true): bool
    {
        if (isset($this->schemaInfo()->getColumns()[$attrName])) {
            /** @var Column $col */
            $col = $this->schemaInfo()->getColumns()[$attrName];
            if(DataType::typeIsTextual($col->getColumnType())){
                $attrValue = strval($attrValue);
            }
            $method = 'set' . str_replace( ' ', '', ucwords( str_replace( ['-', '_'], ' ', $attrName ) ) ) . 'Attr';
            if ($setter && method_exists($this, $method)) {
                $attrValue = $this->$method($attrValue, $this->__data);
            }
            $this->__data[$attrName] = $attrValue;
            return true;
        } else {
            $this->__joinData[$attrName] = $attrValue;
            return false;
        }
    }

    private function __tableArray():array
    {
        $key = "tableArray".$this->__modelhash();
        $ret = RuntimeCache::getInstance()->get($key);
        if(is_array($ret)){
            return $ret;
        }
        $table = $this->schemaInfo();
        $data = $table->getColumns();
        $list = [];
        /** @var Column $col */
        foreach ($data as $col){
            $list[$col->getColumnName()] = $col->getDefaultValue();
        }
        RuntimeCache::getInstance()->set($key,$list);
        return $list;
    }

    private function __modelHash():string
    {
        $key = md5(static::class.$this->tableName().$this->runtimeConfig()->getConnectionConfig()->getName());
        return substr($key,8,16);
    }


}