<?php
/**
 * 结果集
 * User: Siam
 * Date: 2020/4/17
 * Time: 15:27
 */

namespace EasySwoole\ORM\Collection;


use EasySwoole\ORM\AbstractModel;

class Collection extends BaseCollection
{
    /**
     * 设置需要隐藏的输出属性
     * @access public
     * @param array $hidden   属性列表
     * @return $this
     */
    public function hidden($hidden = [])
    {
        $this->each(function ($model) use ($hidden) {
            /** @var AbstractModel $model */
            $model->hidden($hidden);
        });
        return $this;
    }

    /**
     * 设置需要输出的属性
     * @param array $visible
     * @return $this
     */
    public function visible($visible = [])
    {
        $this->each(function ($model) use ($visible) {
            /** @var AbstractModel $model */
            $model->visible($visible);
        });
        return $this;
    }

    /**
     * 设置需要追加的输出属性
     * @access public
     * @param array $append   属性列表
     * @return $this
     */
    public function append($append = [])
    {
        $this->each(function ($model) use ($append) {
            /** @var AbstractModel $model */
            $model && $model->append($append);
        });
        return $this;
    }
}