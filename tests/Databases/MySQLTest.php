<?php
namespace Kir\MySQL\Databases;

use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Common\DBTestCase;
use Kir\MySQL\QueryLogger\ClosureQueryLogger;
use PDOException;
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

	public function testInfoLoggingFromQuery() {
		$log = (object) ['queries' => []];
		$db = $this->getDB();
		$db->getQueryLoggers()->add(new ClosureQueryLogger(function (string $query, float $duration, string $severity) use ($log) {
			$log->queries[] = ['query' => $query, 'durection' => $duration];
		}));
		$query = 'SELECT COUNT(*) FROM test1';
		$db->query($query)->fetchColumn(0);
		self::assertEquals($query, $log->queries[0]['query'] ?? null);
	}

	public function testErrorLoggingFromQuery() {
		$log = (object) ['queries' => []];
		$db = $this->getDB();
		$db->getQueryLoggers()->add(new ClosureQueryLogger(function (string $query, float $duration, string $severity, PDOException $e) use ($log) {
			$log->queries[] = ['query' => $query, 'durection' => $duration, 'exception' => $e];
		}));
		$query = 'SELECT COUNT(*) FROM test1_';
		try {
			$db->query($query)->fetchColumn(0);
		} catch (PDOException $e) {}
		self::assertEquals($query, $log->queries[0]['query'] ?? null);
		self::assertContains('SQLSTATE[42S02]', ($log->queries[0]['exception'] ?? null)->getMessage());
	}

	public function testInfoLoggingFromExec() {
		$log = (object) ['queries' => []];
		$db = $this->getDB();
		$db->getQueryLoggers()->add(new ClosureQueryLogger(function (string $query, float $duration) use ($log) {
			$log->queries[] = ['query' => $query, 'durection' => $duration];
		}));
		$query = 'SET @foo = 1';
		$db->exec($query);
		self::assertEquals($query, $log->queries[0]['query'] ?? null);
	}

	public function testErrorLoggingFromExec() {
		$log = (object) ['queries' => []];
		$db = $this->getDB();
		$db->getQueryLoggers()->add(new ClosureQueryLogger(function (string $query, float $duration, string $severity, PDOException $e) use ($log) {
			$log->queries[] = ['query' => $query, 'durection' => $duration, 'exception' => $e];
		}));
		$query = 'UPDATE x SET y=1';
		try {
			$db->exec($query);
		} catch (PDOException $e) {}
		self::assertEquals($query, $log->queries[0]['query'] ?? null);
		self::assertContains('SQLSTATE[42S02]', ($log->queries[0]['exception'] ?? null)->getMessage());
	}

	public function testInfoLoggingFromGetTableFields() {
		$log = (object) ['queries' => []];
		$db = $this->getDB();
		$db->getQueryLoggers()->add(new ClosureQueryLogger(function (string $query, float $duration) use ($log) {
			$log->queries[] = ['query' => $query, 'durection' => $duration];
		}));
		$query = 'DESCRIBE test1';
		$db->getTableFields('test1');
		self::assertEquals($query, $log->queries[0]['query'] ?? null);
	}

	public function testErrorLoggingFromGetTableFields() {
		$log = (object) ['queries' => []];
		$db = $this->getDB();
		$db->getQueryLoggers()->add(new ClosureQueryLogger(function (string $query, float $duration, string $severity, PDOException $e) use ($log) {
			$log->queries[] = ['query' => $query, 'durection' => $duration, 'exception' => $e];
		}));
		$query = 'DESCRIBE test1_';
		try {
			$db->getTableFields('test1_');
		} catch (PDOException $e) {}
		self::assertEquals($query, $log->queries[0]['query'] ?? null);
		self::assertContains('SQLSTATE[42S02]', ($log->queries[0]['exception'] ?? null)->getMessage());
	}
}
