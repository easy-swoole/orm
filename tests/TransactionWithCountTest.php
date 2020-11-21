<?php
/**
 * Created by PhpStorm.
 * User: Siam
 * Date: 2020/6/8
 * Time: 10:07
 */

namespace EasySwoole\ORM\Tests;


use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Tests\models\TestCastsModel;
use EasySwoole\ORM\Tests\service\TestTransationService;
use PHPUnit\Framework\TestCase;

class TransactionWithCountTest extends TestCase
{
    /**
     * @var $connection Connection
     */
    protected $connection;
    protected $tableName = 'user_test_list';
    private $lastId;

    protected function setUp(): void
    {
        parent::setUp();
        $config = new Config(MYSQL_CONFIG);
        $this->connection = new Connection($config);
        DbManager::getInstance()->addConnection($this->connection);
        $connection = DbManager::getInstance()->getConnection();
        $this->assertTrue($connection === $this->connection);
    }

    public function testClear()
    {
        $res = TestCastsModel::create()->destroy(null, true);
    }


    /** 正常情况下 一次的开启事务 */
    public function testNormalCommit()
    {
        DbManager::getInstance()->startTransactionWithCount();
        $id = TestCastsModel::create([
            "name" => "siam",
            "age" => 21,
            "addTime" => "2020-6-8 10:11:05",
            "state" => 0,
        ])->save();
        $this->assertIsInt($id);

        DbManager::getInstance()->commitWithCount();

        $confirm = TestCastsModel::create()->get($id);
        $this->assertInstanceOf(TestCastsModel::class, $confirm);

    }

    public function testNormalRollback()
    {
        DbManager::getInstance()->startTransactionWithCount();
        $id = TestCastsModel::create([
            "name" => "siam",
            "age" => 21,
            "addTime" => "2020-6-8 10:11:05",
            "state" => 0,
        ])->save();
        $this->assertIsInt($id);

        DbManager::getInstance()->rollbackWithCount();

        $confirm = TestCastsModel::create()->get($id);

        $this->assertEquals(null, $confirm);
    }

    /** 嵌套情况 */
    public function testNestingCommit()
    {
        DbManager::getInstance()->startTransactionWithCount();
        $id = TestCastsModel::create([
            "name" => "siam",
            "age" => 21,
            "addTime" => "2020-6-8 10:11:05",
            "state" => 0,
        ])->save();
        $this->assertIsInt($id);

        $nestingRes = TestTransationService::getUser();
        if (!$nestingRes) {
            DbManager::getInstance()->rollbackWithCount();
            // 如果回滚了 测一下是否正常回滚
            $confirm = TestCastsModel::create()->get($id);
            $this->assertEquals(null, $confirm);
            return false;
        }

        DbManager::getInstance()->commitWithCount();

        $confirm = TestCastsModel::create()->get($id);
        $this->assertInstanceOf(TestCastsModel::class, $confirm);
    }

    public function testNestingRollback()
    {
        TestTransationService::$res = false;

        DbManager::getInstance()->startTransactionWithCount();
        $id = TestCastsModel::create([
            "name" => "siam",
            "age" => 21,
            "addTime" => "2020-6-8 10:11:05",
            "state" => 0,
        ])->save();
        $this->assertIsInt($id);
        $nestingRes = TestTransationService::getUser();
        if (!$nestingRes) {
            DbManager::getInstance()->rollbackWithCount();
            // 如果回滚了 测一下是否正常回滚
            $confirm = TestCastsModel::create()->get($id);
            $this->assertEquals(null, $confirm);
            return false;
        }

        DbManager::getInstance()->commitWithCount();

        $confirm = TestCastsModel::create()->get($id);
        $this->assertInstanceOf(TestCastsModel::class, $confirm);
    }

    /** 处理异常情况 */
    public function testErrorCommit()
    {

    }

    public function testErrorRollback()
    {

    }

    /** 测试处理不完整，自动回滚 */
    public function testForgetDone()
    {
        go(function () {
            DbManager::getInstance()->startTransactionWithCount();
            $id = TestCastsModel::create([
                "name" => "siam",
                "age" => 21,
                "addTime" => "2020-6-8 10:11:05",
                "state" => 0,
            ])->save();
            $this->assertIsInt($id);
            $this->lastId = $id;
        });
        \co::sleep(0.1);

        $confirm = TestCastsModel::create()->get($this->lastId);
        $this->assertEquals(null, $confirm);
    }
}