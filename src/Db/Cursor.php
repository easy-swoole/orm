<?php
/**
 * Created by PhpStorm.
 * User: haoxu
 * Date: 2020-01-15
 * Time: 16:44
 */

namespace EasySwoole\ORM\Db;

use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Exception\Exception;
use phpDocumentor\Reflection\Types\This;
use Swoole\Coroutine\MySQL\Statement;

class Cursor implements CursorInterface
{
    protected $statement;
    protected $modelName;
    protected $returnAsArray = false;

    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }


    public function setModelName(string $modelName)
    {
        $this->modelName = $modelName;
    }

    public function setReturnAsArray(bool $returnAsArray): void
    {
        $this->returnAsArray = $returnAsArray;
    }


    /**
     * @return mixed
     * @throws \Throwable
     */
    public function fetch()
    {
        try{
            $data = $this->statement->fetch();
            if (!$this->returnAsArray && $data) {
                if (is_null($this->modelName)) {
                    throw new Exception('ModelName can not be null');
                }
                $model = new $this->modelName($data);
                return $model;
            }
            return $data;
        }catch (\Throwable $throw){
            throw $throw;
        }
    }
}