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
    /** @var AbstractModel $fatherModel */
    private $fatherModel;
    private $childModelName;

    public function __construct(AbstractModel $model, $class)
    {
        $this->fatherModel = $model;
        $this->childModelName = $class;
    }

    /**
     * @param $where callable 可以闭包调用where、order、field
     * @param $pk string 主表条件字段名
     * @param $insPk string 附表条件字段名
     * @return mixed
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \ReflectionException
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
        $data = $this->fatherModel->toRawArray(false, false);

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

        $builder->where("$insPk", $pkVal);

        if (!empty($where) && is_callable($where)){
            call_user_func($where, $builder);
        }

        $builder->getOne($targetTable, $builder->getField());

        $result = $ins->query($builder);
        if ($result) {
            $targetData = [];
            foreach ($result[0] as $key => $value){
                $targetData[$key] = $value;
            }

            // 强制toArray参数
            $ins->setToArrayNotNull(false);
            $ins->setToArrayStrict(false);

            $ins->data($targetData, false);
            return $ins;
        }
        return null;
    }


    /**
     * @param array $data 原始数据 进入这里的处理都是多条 all查询结果
     * @param $withName string 预查询字段名
     * @param $where
     * @param $pk
     * @param $insPk
     * @param $joinType
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

        $insData  = $insClass->where($insPk, $pks, 'IN')->all(function (QueryBuilder $queryBuilder) use ($where){
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

            $temData[$insV[$insPk]] = $insV;// 以子表主键映射数组
        }
        // 附表insPk的值 = 主表pk的值 这是查询条件
        foreach ($data as $model){
            if (isset($temData[$model[$pk]])){
                $model[$withName] = $temData[$model[$pk]];
            }
        }

        return $data;
    }
}