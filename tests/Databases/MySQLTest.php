<?php
namespace Kir\MySQL\Databases;

use Kir\FakePDO\EventHandlers\RegistryEventHandler;
use Kir\FakePDO\FakePDO;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Traversable;

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

	public function testFetchRow() {
		// Closure w/o return, but with reference
		$row = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRow(function (array &$row) {
			$row['test'] = 10;
		});
		$this->assertEquals(['id' => 1, 'test' => 10], $row);

		// Closure with return
		$row = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRow(function (array $row) {
			$row['test'] = 10;
			return $row;
		});
		$this->assertEquals(['id' => 1, 'test' => 10], $row);
	}

	public function testFetchRows() {
		// Closure w/o return, but with reference
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRows(function (array &$row) {
			$row['test'] = 10;
		});

		$this->assertEquals([['id' => 1, 'test' => 10]], $rows);

		// Closure with return
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRows(function (array $row) {
			$row['test'] = 10;
			return $row;
		});

		$this->assertEquals([['id' => 1, 'test' => 10]], $rows);
	}

	public function testFetchRowsLazy() {
		// Closure w/o return, but with reference
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRowsLazy(function (array &$row) {
			$row['test'] = 10;
		});
		$rows = iterator_to_array($rows);
		$this->assertEquals([['id' => 1, 'test' => 10]], $rows);

		// Closure with return
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRowsLazy(function (array $row) {
			$row['test'] = 10;
			return $row;
		});
		$rows = iterator_to_array($rows);
		$this->assertEquals([['id' => 1, 'test' => 10]], $rows);

		// IgnoredRow
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRowsLazy(function (array $row) {
			$row['test'] = 10;
			return $row;
		});
		$rows = iterator_to_array($rows);
		$this->assertEquals([['id' => 1, 'test' => 10]], $rows);
	}
}
