<?php
/**
 * 多对多关系：一个文章拥有多个标签，一个标签下的文章可以有多个文章
 * User: Siam
 * Date: 2020/2/27
 * Time: 9:03
 */

namespace EasySwoole\ORM\Relations;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;

use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\EasySwoole\Config;

class BelongsToMany
{
    private $fatherModel;
    private $childModelName;
    private $middelTableName;


    /**
     * BelongsToMany constructor.
     * @param AbstractModel $model
     * @param $class
     * @param $middleTableName
     * @param null $pk 中间表储存当前模型主键的字段名
     * @param null $childPk 中间表储存目标模型主键的字段名
     * @throws Exception
     * @throws \ReflectionException
     */
    public function __construct(AbstractModel $model, $class, $middleTableName)
    {
        $this->fatherModel     = $model;
        $this->childModelName  = $class;
        $this->middelTableName = $middleTableName;
    }

    /**
     * 直接查询，单条数据适用
     * @param $where
     * @param $foreignPivotKey
     * @param $relatedPivotKey
     * @param $parentKey
     * @param $relatedKey
     * @param $joinType
     * @throws Exception
     * @throws \Throwable
     */
    public function result($where, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $joinType)
    {
        $ref = new \ReflectionClass($this->childModelName);

        if (!$ref->isSubclassOf(AbstractModel::class)) {
            throw new Exception("relation class must be subclass of AbstractModel");
        }

        /** @var AbstractModel $ins */
        $ins = $ref->newInstance();

        if ($foreignPivotKey === null) {
            $dbName = Config::getInstance()->getConf('MYSQL.database');
            $queryBuilder = new QueryBuilder();
            $queryBuilder->raw("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA='{$dbName}' AND TABLE_NAME='{$this->middelTableName}' AND REFERENCED_TABLE_NAME='{$this->fatherModel->schemaInfo()->getTable()}' AND CONSTRAINT_NAME like 'fk_%';");
            $tableColumns = DbManager::getInstance()->query($queryBuilder, $raw = true, 'default');
            if (!empty($tableColumns->getResult())) {
                $foreignPivotKey = $tableColumns->getResult()[0]['COLUMN_NAME'];
            } else {
                return null;
            }
        }
        if ($relatedPivotKey === null) {
            $dbName = Config::getInstance()->getConf('MYSQL.database');
            $queryBuilder = new QueryBuilder();
            $queryBuilder->raw("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA='{$dbName}' AND TABLE_NAME='{$this->middelTableName}' AND REFERENCED_TABLE_NAME='{$ins->schemaInfo()->getTable()}' AND CONSTRAINT_NAME like 'fk_%';");
            $tableColumns = DbManager::getInstance()->query($queryBuilder, $raw = true, 'default');
            if (!empty($tableColumns->getResult())) {
                $relatedPivotKey = $tableColumns->getResult()[0]['COLUMN_NAME'];
            } else {
                return null;
            }
        }
        if ($parentKey === null) {
            $parentKey = $this->fatherModel->schemaInfo()->getPkFiledName();
        }
        if ($relatedKey === null) {
            $relatedKey = $ins->schemaInfo()->getPkFiledName();
        }

        $builder = new QueryBuilder();
        $pkValue = $this->fatherModel->getAttr($parentKey);
        $builder->raw("SELECT $foreignPivotKey,$relatedPivotKey FROM `{$this->middelTableName}` WHERE `{$foreignPivotKey}` = ? ", [$pkValue]);
        $middleQuery = DbManager::getInstance()->query($builder, true, $this->fatherModel->getConnectionName());

        if (!$middleQuery->getResult()) return null;

        // in查询目标表
        $childPkValue = array_column($middleQuery->getResult(), $relatedPivotKey);

        $childRes = $ins->all([$relatedKey => [$childPkValue, 'in']]);

        return $childRes;
    }

    /**
     * @param array $data 原始数据 进入这里的处理都是多条 all查询结果
     * @param $with string 预查询字段名
     * @param $where
     * @param $foreignPivotKey
     * @param $relatedPivotKey
     * @param $parentKey
     * @param $relatedKey
     * @param $joinType
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    public function preHandleWith(array $data, $with, $where, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $joinType)
    {
        // 如果闭包不为空，则只能执行闭包
        if ($where !== null && is_callable($where)){
            // 闭包的只能一个一个调用
            foreach ($data as $model){
                foreach ($this->fatherModel->getWith() as $with){
                    $model->$with();
                }
            }
            return $data;
        }

        $ref = new \ReflectionClass($this->childModelName);

        if (!$ref->isSubclassOf(AbstractModel::class)) {
            throw new Exception("relation class must be subclass of AbstractModel");
        }

        /** @var AbstractModel $ins */
        $ins = $ref->newInstance();

        if ($foreignPivotKey === null) {
            $dbName = Config::getInstance()->getConf('MYSQL.database');
            $queryBuilder = new QueryBuilder();
            $queryBuilder->raw("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA='{$dbName}' AND TABLE_NAME='{$this->middelTableName}' AND REFERENCED_TABLE_NAME='{$this->fatherModel->schemaInfo()->getTable()}' AND CONSTRAINT_NAME like 'fk_%';");
            $tableColumns = DbManager::getInstance()->query($queryBuilder, $raw = true, 'default');
            if (!empty($tableColumns->getResult())) {
                $foreignPivotKey = $tableColumns->getResult()[0]['COLUMN_NAME'];
            } else {
                return null;
            }
        }
        if ($relatedPivotKey === null) {
            $dbName = Config::getInstance()->getConf('MYSQL.database');
            $queryBuilder = new QueryBuilder();
            $queryBuilder->raw("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA='{$dbName}' AND TABLE_NAME='{$this->middelTableName}' AND REFERENCED_TABLE_NAME='{$ins->schemaInfo()->getTable()}' AND CONSTRAINT_NAME like 'fk_%';");
            $tableColumns = DbManager::getInstance()->query($queryBuilder, $raw = true, 'default');
            if (!empty($tableColumns->getResult())) {
                $relatedPivotKey = $tableColumns->getResult()[0]['COLUMN_NAME'];
            } else {
                return null;
            }
        }
        if ($parentKey === null) {
            $parentKey = $this->fatherModel->schemaInfo()->getPkFiledName();
        }
        if ($relatedKey === null) {
            $relatedKey = $ins->schemaInfo()->getPkFiledName();
        }

        // 逻辑跟result方法中查询基本一致，先获取A表主键数组，从中间表中查询所有符合数据，映射成为二维数组
        // 从B表查询所有数据，根据映射数组设置到A模型数据中
        $pkValue = array_map(function ($v) use ($parentKey){
            return $v->$parentKey;
        }, $data);
        $pkValueStr = implode(',', $pkValue);

        $queryBuilder = new QueryBuilder();
        $queryBuilder->raw("SELECT $foreignPivotKey,$relatedPivotKey FROM `{$this->middelTableName}` WHERE `{$foreignPivotKey}` IN ({$pkValueStr}) ");
        $middleQuery = DbManager::getInstance()->query($queryBuilder, true, $this->fatherModel->getConnectionName());

        if (!$middleQuery->getResult()) return $data;

        $middleDataArray = [];
        $BPkValue = []; // 用于一会IN查询B表
        foreach ($middleQuery->getResult() as $queryData) {
            $APkValue                     = $queryData[$foreignPivotKey];
            $middleDataArray[$APkValue][] = $queryData[$relatedPivotKey];
            $BPkValue[]                   = $queryData[$relatedPivotKey];
        }
        // BPK去重 重置下标
        $BPkValue = array_values(array_unique($BPkValue));
        $BValue   = $ins->all([$relatedKey => [$BPkValue, 'in']]);
        // 映射为以BPK为键的数组
        $BValueByBPK = [];
        foreach ($BValue as $B){
            $BValueByBPK[$B->$relatedKey] = $B;
        }

        // 更新中间二维数组，把pk值映射成model
        foreach ($middleDataArray as $key => $middleOne){
            if (is_array($middleOne)){
                foreach ($middleOne as $tempKey => $tempValue){
                    if (isset($BValueByBPK[$tempValue])){
                        $middleDataArray[$key][$tempKey] = clone $BValueByBPK[$tempValue];
                    }
                }
            }
        }

        // 遍历$data 原始数据，把属于自己的数据放到自己的属性中
        foreach ($data as $model){
            if (isset($middleDataArray[$model->$parentKey])){
                $model[$with] = $middleDataArray[$model->$parentKey];
            }
        }
        return $data;
    }
}