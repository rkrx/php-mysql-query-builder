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
	public function testNestedDryRun(): void {
		$this->getDB()->update()->table('test1')->set('field1', 100)->where(['id' => 1])->run();
		$this->getDB()->dryRun(function () {
			$this->getDB()->update()->table('test1')->set('field1', 101)->where(['id' => 1])->run();
			$this->getDB()->dryRun(function () {
				$this->getDB()->update()->table('test1')->set('field1', 102)->where(['id' => 1])->run();
				$this->getDB()->dryRun(function () {
					$this->getDB()->update()->table('test1')->set('field1', 103)->where(['id' => 1])->run();
					self::assertEquals(103, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
				});
				self::assertEquals(102, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
			});
			self::assertEquals(101, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
		});
		self::assertEquals(100, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
	}

	/**
	 * Test if the outer nested transaction detection works as expected
	 */
	public function testNestedTransactionWithException(): void {
		$this->expectExceptionMessage('TEST');
		$this->getDB()->update()->table('test1')->set('field1', 100)->where(['id' => 1])->run();
		$this->getDB()->transaction(function () {
			try {
				$this->getDB()->update()->table('test1')->set('field1', 101)->where(['id' => 1])->run();
				$this->getDB()->transaction(function () {
					try {
						$this->getDB()->update()->table('test1')->set('field1', 102)->where(['id' => 1])->run();
						$this->getDB()->transaction(function () {
							try {
								$this->getDB()->update()->table('test1')->set('field1', 103)->where(['id' => 1])->run();
								throw new RuntimeException('TEST');
							} finally {
								self::assertEquals(103, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
							}
						});
					} finally {
						self::assertEquals(102, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
					}
				});
			} finally {
				self::assertEquals(101, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
			}
		});
		self::assertEquals(100, $this->getDB()->query('SELECT field1 FROM test1 WHERE id=1')->fetchColumn(0));
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

	public function testFetchValue(): void {
		// Closure w/o return, but with reference
		$value = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchValue();
		self::assertEquals(1, $value);

		$value = TestSelect::create()
		->field('t.id')
		->from('t', 'test#test1')
		->where('t.id=?', 1)
		->fetchValue(null, 'strval');
		self::assertEquals('1', $value);
	}
}
