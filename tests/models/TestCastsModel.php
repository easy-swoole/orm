<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2020/6/4
 * Time: 16:13
 */

namespace EasySwoole\ORM\Tests\models;


use EasySwoole\ORM\AbstractModel;

/**
 * Class TestCastsModel
 * @package EasySwoole\ORM\Tests
 * @property $id
 * @property $name
 * @property $age
 * @property $addTime
 * @property $state
 *
 * @property $test_json
 * @property $test_array
 * @property $test_date
 * @property $test_datetime
 * @property $test_string
 *
 */
class TestCastsModel extends AbstractModel
{
    protected $tableName = 'test_user_model';

    protected $casts = [
        'age'           => 'int',
        'id'            => 'float',
        'addTime'       => 'timestamp',
        'state'         => 'bool',
        // 在join中自定义的
        'test_json'     => 'json',
        'test_array'    => 'array',
        'test_date'     => 'date',
        'test_datetime' => 'datetime',
        'test_string'   => 'string',
    ];
}