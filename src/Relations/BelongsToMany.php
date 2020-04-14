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

class BelongsToMany
{
    /** @var AbstractModel */
    private $fatherModel;
    /** @var AbstractModel */
    private $childModel;

    private $middelTableName;

    private $pk;
    private $childPk;


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
    public function __construct(AbstractModel $model, $class, $middleTableName, $pk = null, $childPk = null)
    {
        $ref = new \ReflectionClass($class);

        if (!$ref->isSubclassOf(AbstractModel::class)) {
            throw new Exception("relation class must be subclass of AbstractModel");
        }

        $this->fatherModel     = $model;
        $this->childModel      = new $class;
        $this->middelTableName = $middleTableName;

        // 如果父级设置客户端，则继承
        if ($this->fatherModel->getExecClient()){
            $this->childModel->setExecClient($this->fatherModel->getExecClient());
        }

        if ($pk!==null){
            $this->pk = $pk;
        }else{
            $this->pk = $this->fatherModel->schemaInfo()->getPkFiledName();
        }
        if ($childPk !== NULL) {
            $this->childPk = $childPk;
        } else {
            $this->childPk = $this->childModel->schemaInfo()->getPkFiledName();
        }
    }

    /**
     * 直接查询，单条数据适用
     * @param callable|null $callable
     * @return array|bool|\EasySwoole\ORM\Db\Cursor|null
     * @throws Exception
     * @throws \Throwable
     */
    public function result(callable $callable = null)
    {
        $pk      = $this->pk;
        $pkValue = $this->fatherModel->getAttr($this->fatherModel->schemaInfo()->getPkFiledName());
        $childPk = $this->childPk;

        $queryBuilder = new QueryBuilder();
        $queryBuilder->raw("SELECT $pk,$childPk FROM `{$this->middelTableName}` WHERE `{$pk}` = ? ", [$pkValue]);

        // 如果父级设置客户端，则继承
        if ($this->fatherModel->getExecClient()){
            $middleQuery = DbManager::getInstance()->query($queryBuilder, true, $this->fatherModel->getExecClient());
        }else{
            $middleQuery = DbManager::getInstance()->query($queryBuilder, true, $this->fatherModel->getConnectionName());
        }

        if (!$middleQuery->getResult()) return null;

        // in查询目标表
        $childPkValue = array_column($middleQuery->getResult(), $childPk);

        $childRes = $this->childModel->all(function (QueryBuilder $builder) use($childPk, $childPkValue, $callable){
            $builder->where($this->childModel->schemaInfo()->getPkFiledName(), $childPkValue, "IN");
            if (is_callable($callable)){
                call_user_func($callable, $builder);
            }
        });

        return $childRes;
    }

    /**
     * @param $data
     * @param $with
     * @param $callable
     * @return mixed
     * @throws Exception
     * @throws \Throwable
     */
    public function preHandleWith($data, $with, callable $callable = null)
    {
        // 逻辑跟result方法中查询基本一致，先获取A表主键数组，从中间表中查询所有符合数据，映射成为二维数组
        // 从B表查询所有数据，根据映射数组设置到A模型数据中

        // 中间表的键名
        $pk      = $this->pk;
        $childPk = $this->childPk;
        // 真实主键名
        $realyPk      = $this->fatherModel->schemaInfo()->getPkFiledName();
        $realyChildPk = $this->childModel->schemaInfo()->getPkFiledName();

        $pkValue = array_map(function ($v) use($realyPk){
            return $v->$realyPk;
        }, $data);
        $pkValueStr = implode(',', $pkValue);

        $queryBuilder = new QueryBuilder();
        $queryBuilder->raw("SELECT $pk,$childPk FROM `{$this->middelTableName}` WHERE `{$pk}` IN ({$pkValueStr}) ");

        // 如果父级设置客户端，则继承
        if ($this->fatherModel->getExecClient()){
            $middleQuery = DbManager::getInstance()->query($queryBuilder, true, $this->fatherModel->getExecClient());
        }else{
            $middleQuery = DbManager::getInstance()->query($queryBuilder, true, $this->fatherModel->getConnectionName());
        }

        if (!$middleQuery->getResult()) return $data;

        $middleDataArray = [];
        $BPkValue = []; // 用于一会IN查询B表
        foreach ($middleQuery->getResult() as $queryData) {
            $APkValue                     = $queryData[$pk];
            $middleDataArray[$APkValue][] = $queryData[$childPk];
            $BPkValue[]                   = $queryData[$childPk];
        }
        // BPK去重 重置下标
        $BPkValue = array_values(array_unique($BPkValue));

        $BValue   = $this->childModel->all(function (QueryBuilder $builder) use($childPk, $BPkValue, $callable){
            $builder->where($this->childModel->schemaInfo()->getPkFiledName(), $BPkValue, "IN");
            if ($callable !== null){
                call_user_func($callable, $builder);
            }
        });
        // 映射为以BPK为键的数组
        $BValueByBPK = [];
        foreach ($BValue as $B){
            $BValueByBPK[$B->$realyChildPk] = $B;
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
            if (isset($middleDataArray[$model->$realyPk])){
                $model[$with] = $middleDataArray[$model->$realyPk];
            }
        }
        return $data;
    }
}