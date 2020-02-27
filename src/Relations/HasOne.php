<?php
/**
 * 一对一
 * User: Siam
 * Date: 2020/2/27
 * Time: 11:21
 */

namespace EasySwoole\ORM\Relations;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Utility\FieldHandle;

class HasOne
{
    private $fatherModel;
    private $childModelName;

    public function __construct(AbstractModel $model, $class)
    {
        $this->fatherModel = $model;
        $this->childModelName = $class;
    }

    /**
     * @param $where
     * @param $pk
     * @param $joinPk
     * @param $joinType
     * @return mixed
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function result($where, $pk, $joinPk, $joinType)
    {
        $ref = new \ReflectionClass($this->childModelName);

        if (!$ref->isSubclassOf(AbstractModel::class)) {
            throw new Exception("relation class must be subclass of AbstractModel");
        }

        /** @var AbstractModel $ins */
        $ins = $ref->newInstance();
        $builder = new QueryBuilder();

        if ($pk === null) {
            $pk = $this->fatherModel->schemaInfo()->getPkFiledName();
        }
        if ($joinPk === null) {
            $joinPk = $ins->schemaInfo()->getPkFiledName();
        }

        $targetTable = $ins->schemaInfo()->getTable();
        $currentTable = $this->fatherModel->schemaInfo()->getTable();

        // 支持复杂的构造
        if ($where) {
            /** @var QueryBuilder $builder */
            $builder = call_user_func($where, $builder);
            $this->fatherModel->preHandleQueryBuilder($builder);
            $builder->getOne($targetTable, $builder->getField());
        } else {
            $targetTableAlias = "ES_INS";
            // 关联表字段自动别名
            $fields = FieldHandle::parserRelationFields($this->fatherModel, $ins, $targetTableAlias);

            $builder->join($targetTable." AS {$targetTableAlias}", "{$targetTableAlias}.{$joinPk} = {$currentTable}.{$pk}", $joinType)
                ->where("{$currentTable}.{$pk}", $this->fatherModel->$pk);
            $this->fatherModel->preHandleQueryBuilder($builder);
            $builder->getOne($currentTable, $fields);
        }

        $result = $this->fatherModel->query($builder);
        if ($result) {
            // 分离结果 两个数组
            $targetData = [];
            $originData = [];
            foreach ($result[0] as $key => $value){
                if (isset($targetTableAlias)) {
                    // 如果有包含附属别名，则是targetData
                    if (strpos($key, $targetTableAlias) !==  false){
                        $trueKey = ltrim($key, $targetTableAlias."_");
                        $targetData[$trueKey] = $value;
                    }else{
                        $originData[$key] = $value;
                    }
                }else{
                    $targetData[$key] = $value;
                }
            }

            $this->fatherModel->data($originData, false);
            $ins->data($targetData, false);

            return $ins;
        }
        return null;
    }
}