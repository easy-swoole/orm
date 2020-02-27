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
     * @throws Exception
     * @throws \Throwable
     */
    public function result()
    {
        $pk      = $this->pk;
        $pkValue = $this->fatherModel->getAttr($pk);
        $childPk = $this->childPk;

        $queryBuilder = new QueryBuilder();
        $queryBuilder->raw("SELECT $pk,$childPk FROM `{$this->middelTableName}` WHERE `{$pk}` = ? ", [$pkValue]);
        $middleQuery = DbManager::getInstance()->query($queryBuilder, true, $this->fatherModel->getConnectionName());

        if (!$middleQuery->getResult()) return null;

        // in查询目标表
        $childPkValue = array_column($middleQuery->getResult(), $childPk);

        $childRes = $this->childModel->all($childPkValue);

        return $childRes;
    }

    /**
     * 预查询，需要考虑是单条还是多条数据
     */
    public function preHandleWith()
    {

    }
}