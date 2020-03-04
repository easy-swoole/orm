<?php

namespace EasySwoole\ORM\Concern;

use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Db\Cursor;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\ORM\Relations\BelongsToMany;
use EasySwoole\ORM\Relations\HasMany;
use EasySwoole\ORM\Relations\HasOne;

/**
 * 模型关联处理
 */
trait RelationShip
{

    /** @var bool 是否为预查询 */
    private $preHandleWith = false;

    /** @var array 预查询 */
    private $with;

    /**
     * 一对一关联
     * @param string        $class
     * @param callable|null $where
     * @param null          $pk
     * @param null          $joinPk
     * @param string        $joinType
     * @return mixed|null
     * @throws \Throwable
     */
    protected function hasOne(string $class, callable $where = null, $pk = null, $joinPk = null, $joinType = '')
    {
        if ($this->preHandleWith === true){
            return [$class, $where, $pk, $joinPk, $joinType, 'hasOne'];
        }

        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }
        $result = (new HasOne($this, $class))->result($where, $pk, $joinPk, $joinType);
        $this->_joinData[$fileName] = $result;
        return $result;
    }

    /**
     * 一对多关联
     * @param string        $class
     * @param callable|null $where
     * @param null          $pk
     * @param null          $joinPk
     * @param string        $joinType
     * @return mixed|null
     * @throws
     */
    protected function hasMany(string $class, callable $where = null, $pk = null, $joinPk = null, $joinType = '')
    {
        if ($this->preHandleWith === true){
            return [$class, $where, $pk, $joinPk, $joinType, 'hasMany'];
        }
        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }
        $result = (new HasMany($this, $class))->result($where, $pk, $joinPk, $joinType);
        $this->_joinData[$fileName] = $result;
        return $result;
    }

    /**
     * 多对多关联
     * @param string $class
     * @param $middleTableName
     * @param null $pk
     * @param null $childPk
     * @return array|bool|Cursor|mixed|null
     * @throws Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected function belongsToMany(string $class, $middleTableName, callable $where = null, $foreignPivotKey = null, $relatedPivotKey = null,
    $parentKey = null, $relatedKey = null, $joinType = '')
    {
        if ($this->preHandleWith === true){
            return [$class, $middleTableName, $where, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $joinType, 'belongsToMany'];
        }
        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }
        $result = (new BelongsToMany($this, $class, $middleTableName))->result($where, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $joinType);
        $this->_joinData[$fileName] = $result;
        return $result;
    }

    /**
     * 关联预查询
     * @param $data
     * @return mixed
     * @throws \Throwable
     */
    private function preHandleWith($data)
    {
        // $data 只有一条 直接foreach调用 $data->$with();
        if ($data instanceof AbstractModel){// get查询使用
            foreach ($this->with as $with){
                $data->$with();
            }
            return $data;
        }else if (is_array($data) && !empty($data)){// all查询使用
            foreach ($this->with as $with){
                $data[0]->preHandleWith = true;
                list($class, $where, $pk, $joinPk, $joinType, $withType) = $data[0]->$with();
                if (!in_array($withType, ['hasOne', 'hasMany'])) {
                    list($class, $middleTableName, $where, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $joinType, $withType) = $data[0]->$with();
                }
                $data[0]->preHandleWith = false;
                switch ($withType){
                    case 'hasOne':
                        $data = (new HasOne($this, $class))->preHandleWith($data, $with, $where, $pk, $joinPk, $joinType);
                        break;
                    case 'hasMany':
                        $data = (new HasMany($this, $class))->preHandleWith($data, $with, $where, $pk, $joinPk, $joinType);
                        break;
                    case 'belongsToMany':
                        $data = (new BelongsToMany($this, $class, $middleTableName))->preHandleWith($data, $with, $where, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $joinType);
                        break;
                    default:
                        break;
                }
            }
            return $data;
        }
        return $data;
    }

    /**
     * 返回设置的需要预查询的数组列表
     * @return array
     */
    public function getWith()
    {
        return $this->with;
    }

}