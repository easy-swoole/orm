<?php
/**
 * 字段处理工具类
 * User: Siam
 * Date: 2020/2/27
 * Time: 14:14
 */

namespace EasySwoole\ORM\Utility;


use EasySwoole\ORM\AbstractModel;

class FieldHandle
{
    /**
     * 关联查询 字段自动别名解析
     * @param AbstractModel $model
     * @param AbstractModel $ins
     * @param string $insAlias
     * @return array
     * @throws \EasySwoole\ORM\Exception\Exception
     */
    public static function parserRelationFields($model, $ins, $insAlias)
    {
        $currentTable = $model->schemaInfo()->getTable();
        $insFields = array_keys($ins->schemaInfo()->getColumns());
        $fields    = [];
        $fields[]  = "{$currentTable}.*";
        foreach ($insFields as $field){
            $fields[] = "{$insAlias}.{$field} AS {$insAlias}_{$field}";
        }
        return $fields;
    }
}