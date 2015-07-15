<?php
namespace Kir\MySQL\Databases;

use Kir\FakePDO\EventHandlers\RegistryEventHandler;
use Kir\FakePDO\FakePDO;

class MySQLTest extends \PHPUnit_Framework_TestCase {
	/** @var TestDB */
	private $db = null;

	protected function setUp() {
		$this->db = new TestDB();
		$this->db->install();
	}

	protected function tearDown() {
		$this->db->uninstall();
	}

	/**
	 * Check if exceptions are thrown after a certain amount of tries
	 */
	public function testTransactionTries1() {
		$this->setExpectedException('Exception', '5');

		$this->db->transaction(5, function () {
			static $i;
			$i++;
			throw new \Exception($i);
		});
	}

	/**
	 * Check if the number of tries exactly corresponds to the actual amount of tries needed
	 */
	public function testTransactionTries2() {
		$result = $this->db->transaction(5, function () {
			static $i;
			$i++;
			// Fail four times, succeed at the fifth time
			if($i < 5) {
				throw new \Exception($i);
			}
			return $i;
		});

		$this->assertEquals(5, $result);
	}

	public function testGetTableFields() {
		$eventHandler = new RegistryEventHandler();

		$eventHandler->add('PDOStatement::fetchAll', function ($event) {
			return [['Field' => 'a'], ['Field' => 'b'], ['Field' => 'c']];
		});

		$pdo = new FakePDO($eventHandler);

		$mysql = new MySQL($pdo);
		$mysql->getAliasRegistry()->add('test', 'test__');
		$fields = $mysql->getTableFields('test#table');

		$this->assertEquals(array('a', 'b', 'c'), $fields);
	}

	/**
	 * Test if the outer nested transaction detection works as expected
	 */
	public function testNestedTransaction() {
		$eventHandler = new RegistryEventHandler();
		$pdo = new FakePDO($eventHandler);
		$mysql = new MySQL($pdo);

		$mysql->transactionStart();

		$eventHandler->add('PDO::beginTransaction', function () {
			throw new \Exception('Invalid transaction state');
		});

		$eventHandler->add('PDO::rollback', function () {
			throw new \Exception('Invalid transaction state');
		});

		$mysql->transactionStart();
		$mysql->transactionStart();
		$mysql->transactionRollback();
		$mysql->transactionRollback();

		$eventHandler->add('PDO::rollback', function () {
		});

		$mysql->transactionRollback();
	}

	/**
	 * Test if the outer nested transaction detection works as expected
	 */
	public function testOuterNestedTransaction() {
		$eventHandler = new RegistryEventHandler();
		$pdo = new FakePDO($eventHandler);
		$mysql = new MySQL($pdo);

		$pdo->beginTransaction();

		$eventHandler->add('PDO::beginTransaction', function () {
			throw new \Exception('Invalid transaction state');
		});

		$eventHandler->add('PDO::rollback', function () {
			throw new \Exception('Invalid transaction state');
		});

		$mysql->transactionStart();
		$mysql->transactionStart();
		$mysql->transactionStart();
		$mysql->transactionRollback();
		$mysql->transactionRollback();
		$mysql->transactionRollback();
	}
}
