<?php


namespace EasySwoole\ORM\Utility;


use EasySwoole\DDL\Enum\DataType;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Utility\Schema\Column;

class PreProcess
{
    public static function mappingWhere(QueryBuilder $builder, $whereVal, AbstractModel $model)
    {
        // 处理查询条件
        $primaryKey = $model->schemaInfo()->getPkFiledName();
        if (is_int($whereVal)) {
            if (empty($primaryKey)) {
                throw new Exception('Table not have primary key, so can\'t use Model::get($pk)');
            } else {
                $builder->where($primaryKey, $whereVal);
            }
        } else if (is_string($whereVal)) {
            $whereKeys = explode(',', $whereVal);
            $builder->where($primaryKey, $whereKeys, 'IN');
        } else if (is_array($whereVal)) {
            // 如果不相等说明是一个键值数组 需要批量操作where
            if (array_keys($whereVal) !== range(0, count($whereVal) - 1)) {
                foreach ($whereVal as $whereFiled => $whereProp) {
                    if (is_array($whereProp)) {
                        $builder->where($whereFiled, ...$whereProp);
                    } else {
                        $builder->where($whereFiled, $whereProp);
                    }
                }
            } else {  // 否则是一个索引数组 表示查询主键
                $builder->where($primaryKey, $whereVal, 'IN');
            }
        } else if (is_callable($whereVal)) {
            call_user_func($whereVal,$builder);
        }
        return $builder;
    }

    /**
     * 处理数据集的格式
     * 数据入模型之前 通过此方法处理字段格式 以及字段过滤
     * @param array $data 需要设置的数据
     * @param AbstractModel $model
     * @param bool $isInitValue 初始化会将没传入的字段设置为null
     * @return array
     */
    public static function dataFormat(array $data,AbstractModel $model,bool $isInitValue = false)
    {
        $tempData = [];
        foreach ($model->schemaInfo()->getColumns() as $columnName => $column) {
            if (isset($data[$columnName])) {
                $tempData[$columnName] = self::dataValueFormat($data[$columnName], $column);
            } else {
                $isInitValue && $tempData[$columnName] = null;
            }
        }
        return $tempData;
    }

    /**
     * 处理定义值的格式
     * @param mixed $data
     * @param Column $column
     * @return float|int|mixed|string
     */
    public static function dataValueFormat($data, Column $column)
    {
        if (DataType::typeIsTextual($column->getColumnType()) && $data !== null) {
            return strval($data);
        } else {
            return $data;
        }
    }
}