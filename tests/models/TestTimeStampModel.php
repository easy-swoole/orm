<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/3 0003
 * Time: 0:04
 */

namespace EasySwoole\ORM\Tests\models;


use EasySwoole\ORM\AbstractModel;

/**
 * Class TestTimeStampModel
 * @package EasySwoole\ORM\Tests
 * @property mixed $id
 * @property mixed $name
 * @property mixed $age
 * @property mixed $create_at
 * @property mixed $update_at
 * @property mixed $create_time
 * @property mixed $update_time
 */
class TestTimeStampModel extends AbstractModel
{
    protected $tableName='tiamstamp_test';

    protected $autoTimeStamp = true;
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';

    public function setAutoTime($value){
        $this->autoTimeStamp = $value;
    }
    public function setCreateTime($value){
        $this->createTime = $value;
    }
    public function setUpdateTime($value){
        $this->updateTime = $value;
    }
}