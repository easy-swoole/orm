<?php
/**
 * 一对多
 * User: Siam
 * Date: 2020/2/27
 * Time: 11:21
 */

namespace EasySwoole\ORM\Relations;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Utility\FieldHandle;

class HasMany
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
            $builder->get($targetTable, null, $builder->getField());
        } else {
            $targetTableAlias = "ES_INS";
            // 关联表字段自动别名
            $fields = FieldHandle::parserRelationFields($this->fatherModel, $ins, $targetTableAlias);

            $builder->join($targetTable." AS {$targetTableAlias}", "{$targetTableAlias}.{$joinPk} = {$currentTable}.{$pk}", $joinType)
                ->where("{$currentTable}.{$pk}", $this->fatherModel->$pk);
            $this->fatherModel->preHandleQueryBuilder($builder);
            $builder->get($currentTable, null, $fields);
        }

        $result = $this->fatherModel->query($builder);

        if ($result) {
            $return = [];
            foreach ($result as $one) {
                // 分离结果 两个数组
                $targetData = [];
                $originData = [];
                foreach ($one as $key => $value){
                    if(isset($targetTableAlias)){
                        // 如果有包含附属别名，则是targetData
                        if (strpos($key, $targetTableAlias) !==  false){
                            $trueKey = ltrim($key, $targetTableAlias."_");
                            $targetData[$trueKey] = $value;
                        }else{
                            $originData[$key] = $value;
                        }
                    }else{
                        // callable $where 自行处理字段
                        $targetData[$key] = $value;
                    }
                }
                $return[] = ($ref->newInstance())->data($targetData);
            }

            return $return;
        }
        return [];
    }
}