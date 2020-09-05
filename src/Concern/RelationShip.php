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

    /** @var bool $supplyPk 设置了fields  但fields中不包含需要的主键，则自动补充 */
    private $supplyPk = true;

    /**
     * 一对一关联
     * @param string        $class
     * @param callable|null $where
     * @param null          $pk
     * @param null          $insPk
     * @return mixed|null
     * @throws \Throwable
     */
    protected function hasOne(string $class, callable $where = null, $pk = null, $insPk = null)
    {
        if ($this->preHandleWith === true){
            return [$class, $where, $pk, $insPk, '', 'hasOne'];
        }

        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }
        $result = (new HasOne($this, $class))->result($where, $pk, $insPk);
        $this->_joinData[$fileName] = $result;
        return $result;
    }

    /**
     * 一对多关联
     * @param string        $class
     * @param callable|null $where
     * @param null          $pk
     * @param null          $joinPk
     * @return mixed|null
     * @throws
     */
    protected function hasMany(string $class, callable $where = null, $pk = null, $joinPk = null)
    {
        if ($this->preHandleWith === true){
            return [$class, $where, $pk, $joinPk, '', 'hasMany'];
        }
        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }
        $result = (new HasMany($this, $class))->result($where, $pk, $joinPk);
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
    protected function belongsToMany(string $class, $middleTableName, $pk = null, $childPk = null, callable $callable = null)
    {
        if ($this->preHandleWith === true){
            return [$class, $callable, $pk, $childPk, $middleTableName, 'belongsToMany'];
        }
        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }
        $result = (new BelongsToMany($this, $class, $middleTableName, $pk, $childPk))->result($callable);
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
            foreach ($this->with as $with => $params){
                // with为数字 无需传递参数 直接调用该方法
                if (is_numeric($with)) {
                    call_user_func([$data, $params]);
                } else {
                    call_user_func([$data,$with], $params);
                }
            }
            return $data;
        }else if (is_array($data) && !empty($data)){// all查询使用
            foreach ($this->with as $with => $params){

                $data[0]->preHandleWith = true;
                if (is_numeric($with)) {
                    $with = $params;
                    $withFuncResult = call_user_func([$data[0], $with]);
                }else{
                    $withFuncResult = call_user_func([$data[0], $with], $params);
                }
                list($class, $where, $pk, $joinPk, $joinType, $withType) = $withFuncResult;
                $data[0]->preHandleWith = false;

                switch ($withType){
                    case 'hasOne':
                        $data = (new HasOne($this, $class))->preHandleWith($data, $with, $where, $pk, $joinPk);
                        break;
                    case 'hasMany':
                        $data = (new HasMany($this, $class))->preHandleWith($data, $with, $where, $pk, $joinPk);
                        break;
                    case 'belongsToMany':
                        $middleTableName = $joinType;
                        $callable = $where;
                        $data = (new BelongsToMany($this, $class, $middleTableName, $pk, $joinPk))->preHandleWith($data, $with, $callable);
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