<?php
/**
 * Created by PhpStorm.
 * User: haoxu
 * Date: 2020-01-15
 * Time: 16:49
 */

namespace EasySwoole\ORM\Db;


use Swoole\Coroutine\MySQL\Statement;

interface CursorInterface
{
    public function __construct(Statement $statement);

    public function setModelName(string $modelName);

    public function fetch();
}