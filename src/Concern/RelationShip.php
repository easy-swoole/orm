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
    protected function belongsToMany(string $class, $middleTableName, $pk = null, $childPk = null)
    {
        if ($this->preHandleWith === true){
            return [$class, $middleTableName, 'belongsToMany'];
        }
        $fileName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->_joinData[$fileName])) {
            return $this->_joinData[$fileName];
        }
        $result = (new BelongsToMany($this, $class, $middleTableName, $pk, $childPk))->result();
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
            // $data 是多条，需要先提取主键数组，select 副表 where joinPk in (pk arrays);
            // foreach 判断主键，设置值
            foreach ($this->with as $with){
                $data[0]->preHandleWith = true;
                list($class, $where, $pk, $joinPk, $joinType, $withType) = $data[0]->$with();
                if ($pk !== null && $joinPk !== null){
                    $pks = array_map(function ($v) use ($pk){
                        return $v->$pk;
                    }, $data);
                    /** @var AbstractModel $insClass */
                    $insClass = new $class;
                    $insData  = $insClass->where($joinPk, $pks, 'IN')->all();
                    $temData  = [];
                    foreach ($insData as $insK => $insV){
                        if ($withType=='hasOne'){
                            $temData[$insV[$pk]] = $insV;
                        }else if($withType=='hasMany'){
                            $temData[$insV[$pk]][] = $insV;
                        }
                    }
                    foreach ($data as $model){
                        if (isset($temData[$model[$pk]])){
                            $model[$with] = $temData[$model[$pk]];
                        }
                    }
                    $data[0]->preHandleWith = false;
                } else {
                    // 闭包的只能一个一个调用
                    foreach ($data as $model){
                        foreach ($this->with as $with){
                            $model->$with();
                        }
                    }
                }
            }
            return $data;
        }
        return $data;
    }

}