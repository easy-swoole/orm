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
    /** @var AbstractModel $fatherModel */
    private $fatherModel;
    private $childModelName;

    public function __construct(AbstractModel $model, $class)
    {
        $this->fatherModel = $model;
        $this->childModelName = $class;
    }

    /**
     * @param $where callable 可以闭包调用where、order、limit
     * @param $pk
     * @param $insPk string 附表条件字段名
     * @return mixed
     * @throws \Throwable
     */
    public function result($where, $pk, $insPk)
    {
        $ref = new \ReflectionClass($this->childModelName);

        if (!$ref->isSubclassOf(AbstractModel::class)) {
            throw new Exception("relation class must be subclass of AbstractModel");
        }

        /** @var AbstractModel $ins */
        $ins = $ref->newInstance();
        $builder = new QueryBuilder();

        // 如果父级设置客户端，则继承
        if ($this->fatherModel->getExecClient()){
            $ins->setExecClient($this->fatherModel->getExecClient());
        }

        if ($pk === null) {
            $pk = $this->fatherModel->schemaInfo()->getPkFiledName();
        }

        // 代码执行到这一步 说明父级数据是肯定存在的
        $data = $this->fatherModel->toRawArray(false,false);

        $pkVal = $this->fatherModel->$pk;

        // 此pk不存在 data 中
        if (!array_key_exists($pk, $data)){
            throw new Exception("relation pk value must be set");
        }

        // 此pk val为空 直接返回null
        if (empty($pkVal) || is_null($pkVal)) {
            return null;
        }

        if ($insPk === null) {
            $insPk = $ins->schemaInfo()->getPkFiledName();
        }

        $targetTable = $ins->schemaInfo()->getTable();

        $builder->where($insPk, $pkVal);

        if (!empty($where) && is_callable($where)){
            call_user_func($where, $builder);
        }

        $builder->get($targetTable, null, $builder->getField());

        $result = $ins->query($builder);

        if ($result) {
            $return = [];
            foreach ($result as $one) {
                /** @var AbstractModel $childModel */
                $childModel = $ref->newInstance();
                // 强制toArray参数
                $childModel->setToArrayNotNull(false);
                $childModel->setToArrayStrict(false);

                $return[] = $childModel->data($one, false);
            }

            return $return;
        }
        return [];
    }


    /**
     * @param array $data 原始数据 进入这里的处理都是多条 all查询结果
     * @param $withName string 预查询字段名
     * @param $where
     * @param $pk
     * @param $insPk
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    public function preHandleWith(array $data, $withName, $where, $pk, $insPk)
    {
        // 需要先提取主键数组，select 副表 where joinPk in (pk arrays);
        // foreach 判断主键，设置值
        $pks = array_map(function ($v) use ($pk){
            return $v->$pk;
        }, $data);
        $pks = array_values(array_unique($pks));

        /** @var AbstractModel $insClass */
        $insClass = new $this->childModelName;

        // 如果父级设置客户端，则继承
        if ($this->fatherModel->getExecClient()){
            $insClass->setExecClient($this->fatherModel->getExecClient());
        }

        $insData  = $insClass->where($insPk, $pks, 'IN')->all(function (QueryBuilder $queryBuilder) use($where){
            if (is_callable($where)){
                call_user_func($where, $queryBuilder);
            }
        });
        $temData  = [];

        /** @var AbstractModel $insV */
        foreach ($insData as $insK => $insV){

            // 强制toArray参数
            $insV->setToArrayNotNull(false);
            $insV->setToArrayStrict(false);

            $temData[$insV[$insPk]][] = $insV;
        }
        // ins表中的insPk = 主表.pk  这是查询条件
        foreach ($data as $model){
            if (isset($temData[$model[$pk]])){ // 如果在二维数组中，有属于A表模型主键的，那么就是它的子数据
                $model[$withName] = $temData[$model[$pk]];
            }
        }

        return $data;
    }
}
