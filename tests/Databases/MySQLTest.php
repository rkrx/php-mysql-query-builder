<?php
namespace Kir\MySQL\Databases;

use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Common\DBTestCase;
use RuntimeException;

class MySQLTest extends DBTestCase {
	public function testGetTableFields(): void {
		$fields = $this->getDB()->getTableFields('test1');
		self::assertEquals(['id', 'field1', 'field2', 'field3', 'field4'], $fields);
	}

	/**
	 * Test if the outer nested transaction detection works as expected
	 */
	public function testNestedTransaction(): void {
		$this->getDB()->transactionStart();
		$this->getDB()->transactionStart();
		$this->getDB()->transactionStart();
		$this->getDB()->transactionRollback();
		$this->getDB()->transactionRollback();
		$this->getDB()->transactionRollback();
		self::assertTrue(true);
	}

	public function testFetchRow(): void {
		// Closure w/o return, but with reference
		$row = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRow(function (array &$row) {
			$row['test'] = 10;
		});
		self::assertEquals(['id' => 1, 'test' => 10], $row);

		// Closure with return
		$row = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRow(function (array $row) {
			$row['test'] = 10;
			return $row;
		});
		self::assertEquals(['id' => 1, 'test' => 10], $row);
	}

	public function testFetchRows(): void {
		// Closure w/o return, but with reference
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRows(function (array &$row) {
			$row['test'] = 10;
		});

		self::assertEquals([['id' => 1, 'test' => 10]], $rows);

		// Closure with return
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRows(function (array $row) {
			$row['test'] = 10;
			return $row;
		});

		self::assertEquals([['id' => 1, 'test' => 10]], $rows);
	}

	public function testFetchRowsLazy(): void {
		// Closure w/o return, but with reference
		$rows = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchRowsLazy(function (array &$row) {
			$row['test'] = 10;
		});
		$rows = iterator_to_array($rows);
		self::assertEquals([['id' => 1, 'test' => 10]], $rows);

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
		self::assertEquals([['id' => 1, 'test' => 10]], $rows);

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
		self::assertEquals([['id' => 1, 'test' => 10]], $rows);
	}
}
