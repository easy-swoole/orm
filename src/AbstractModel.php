<?php


namespace EasySwoole\ORM;


use EasySwoole\ORM\Characteristic\ArrayAccess;
use EasySwoole\ORM\Characteristic\Base;
use EasySwoole\ORM\Characteristic\Iterator;
use EasySwoole\ORM\Characteristic\JsonSerializable;
use EasySwoole\ORM\Driver\Result;
use EasySwoole\ORM\Driver\QueryBuilder;
use EasySwoole\ORM\Exception\Exception;

/**
 * Class AbstractModel
 * @package EasySwoole\ORM
 */
abstract class AbstractModel implements \ArrayAccess,\Iterator,\JsonSerializable
{
    use Base;
    use Iterator;
    use JsonSerializable;
    use ArrayAccess;

    private $queryBuilder;

    protected $connection = 'default';

    protected $queryResult;

    protected $pk = null;

    protected $limit = null;

    private $withTotalCount = false;

    function __construct()
    {
        $this->queryBuilder = new QueryBuilder();
        $this->initialize();
    }

    abstract protected function table():string ;

    protected function initialize()
    {

    }

    function connect(string $name)
    {
        $this->connection = $name;
        return $this;
    }

    public static function create(array $data = null)
    {
        $ret = new static();
        if($data){
            $ret->setData($data,true);
        }
        return $ret;
    }

    function find($data = null)
    {
        if($data !== null && !empty($this->pk)){
            $this->where($this->pk,$data);
        }
        $this->queryBuilder()->getOne($this->table());
        return $this->execQueryBuilder();
    }

    function limit(int $one,?int $two = null)
    {
        if($two !== null){
            $this->limit = [$one,$two];
        }else{
            $this->limit = $one;
        }
        return $this;
    }

    function withTotalCount()
    {
        if(!$this->withTotalCount){
            $this->queryBuilder()->withTotalCount();
            $this->withTotalCount = true;
        }
        return $this;
    }

    function save()
    {

    }

    public function query(string $sql,array $bindParams = [])
    {
        $ret = DbManager::getInstance()->getConnection($this->connection)->query($sql,$bindParams);
        if($ret){
            $this->queryResult = $ret;
            if($ret->getLastErrorNo()){
                throw new Exception($ret->getLastError());
            }else{
                if($this->withTotalCount){
                    $data = DbManager::getInstance()->getConnection($this->connection)->query('SELECT FOUND_ROWS() as count');
                    if($data->getResult()){
                        $ret->setTotalCount($data->getResult()[0]['count']);
                    }
                }
                return $ret->getResult();
            }
        }
        $this->reset();
        return null;
    }

    public function queryBuilder():QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function execQueryBuilder()
    {
        return $this->query($this->queryBuilder()->getPrepareQuery(),$this->queryBuilder()->getBindParams());
    }

    public function getQueryResult():?Result
    {
        return $this->queryResult;
    }

    function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->queryBuilder->where($whereProp, $whereValue, $operator, $cond);
        return $this;
    }

    function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        $this->where($whereProp, $whereValue, $operator, 'OR');
        return $this;
    }

    function join($joinTable, $joinCondition, $joinType = '')
    {
        $this->queryBuilder->join($joinTable, $joinCondition, $joinType);
        return $this;
    }

    function get($columns = '*')
    {
        $this->queryBuilder()->get($this->table(),$this->limit,$columns);
        return $this->execQueryBuilder();
    }

    function delete()
    {
        $this->queryBuilder()->delete($this->table(),$this->limit);
        return $this->execQueryBuilder();
    }

    function update(?array $data = null,array $columns = [])
    {
        if(!$data){
            $data = $this->data;
        }
        $this->queryBuilder->update($this->table(),$data,$this->limit);
        return $this->execQueryBuilder();
    }

    private function reset()
    {
        $this->limit = null;
        $this->queryBuilder()->reset();
        $this->withTotalCount = false;
    }
}