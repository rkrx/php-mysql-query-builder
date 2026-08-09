<?php
namespace Kir\MySQL\Databases;

use Kir\MySQL\Builder\PreparedSelect;
use Kir\MySQL\Databases\Mock\MockDatabase;
use PHPUnit\Framework\TestCase;
use stdClass;

class MockDatabaseTest extends TestCase {
	public function testFetchAllReturnsDataFromStackInFifoOrder(): void {
		$db = new MockDatabase();
		$db->addToDQLStack([
			['id' => 1, 'name' => 'Foo'],
			['id' => 2, 'name' => 'Bar'],
		]);
		$db->addToDQLStack([
			['id' => 3, 'name' => 'Baz'],
		]);

		$this->assertSame(
			[['id' => 1, 'name' => 'Foo'], ['id' => 2, 'name' => 'Bar']],
			$db->select('name')->from('table')->fetchAll()
		);
		$this->assertSame(
			[['id' => 3, 'name' => 'Baz']],
			$db->select()->from('table')->fetchAll()
		);
	}

	public function testFetchRowAndFetchValueShapeData(): void {
		$db = new MockDatabase();
		$db->addToDQLStack([
			['id' => 5, 'name' => 'Single'],
			['id' => 6, 'name' => 'Ignored'],
		]);
		$db->addToDQLStack([
			['id' => 7, 'name' => 'Another'],
		]);

		$this->assertSame(['id' => 5, 'name' => 'Single'], $db->select()->fetchRow());
		$this->assertSame(7, $db->select()->fetchValue());
	}

	public function testFetchIndexedRowsAndFetchValues(): void {
		$db = new MockDatabase();
		$db->addToDQLStack([
			['id' => 1, 'name' => 'Foo'],
			['id' => 2, 'name' => 'Bar'],
		]);
		$db->addToDQLStack([
			['id' => 3, 'name' => 'Baz'],
		]);

		$this->assertSame([[1, 'Foo'], [2, 'Bar']], $db->select()->fetchIndexedRows());
		$this->assertSame([3], $db->select()->fetchValues());
	}

	public function testPreparedSelectReusesPreparedStatementForFetchMethods(): void {
		$db = new MockDatabase();
		$rows = [
			['id' => 1, 'name' => 'Foo'],
			['id' => 2, 'name' => 'Bar'],
		];
		$db->addToDQLStack($rows);
		$db->addToDQLStack([
			['id' => 3, 'name' => 'Baz'],
		]);
		for($i = 0; $i < 10; $i++) {
			$db->addToDQLStack($rows);
		}

		$prepared = $db->select()->from('table')->prepare();

		$this->assertInstanceOf(PreparedSelect::class, $prepared);
		$this->assertSame([[1, 'Foo'], [2, 'Bar']], $prepared->fetchIndexedRows());
		$this->assertSame([3], $prepared->fetchValues());
		$this->assertSame($rows, $prepared->fetchRows());
		$this->assertSame($rows, iterator_to_array($prepared->fetchRowsLazy()));
		$this->assertSame($rows[0], $prepared->fetchRow());

		$objects = $prepared->fetchObjects();
		$this->assertSame('Foo', $objects[0]->name);
		$this->assertSame('Bar', $objects[1]->name);

		$lazyObjects = iterator_to_array($prepared->fetchObjectsLazy(stdClass::class));
		$this->assertSame('Foo', $lazyObjects[0]->name);
		$this->assertSame('Bar', $lazyObjects[1]->name);
		$this->assertSame('Foo', $prepared->fetchObject(stdClass::class)->name);
		$this->assertSame([1 => 'Foo', 2 => 'Bar'], $prepared->fetchKeyValue());
		$this->assertSame(['Foo' => [$rows[0]], 'Bar' => [$rows[1]]], $prepared->fetchGroups(['name']));
		$this->assertSame([1, 2], $prepared->fetchArray());
		$this->assertSame(1, $prepared->fetchValue());
	}

	public function testPreparedSelectProvidesFoundRows(): void {
		$db = new MockDatabase();
		$db->addToDQLStack([
			['id' => 1],
		]);
		$db->addToDQLStack([
			['found_rows' => 10],
		]);

		$prepared = $db->select()->setCalcFoundRows()->limit(1)->prepare();

		$this->assertSame([['id' => 1]], $prepared->fetchRows());
		$this->assertSame(10, $prepared->getFoundRows());
	}

	public function testFetchObjectsTurnsRowsIntoObjects(): void {
		$db = new MockDatabase();
		$db->addToDQLStack([
			['id' => 1, 'name' => 'Foo'],
			['id' => 2, 'name' => 'Bar'],
		]);

		/** @var array<int, stdClass> $objects */
		$objects = $db->select()->fetchObjects();
		$this->assertCount(2, $objects);
		$this->assertSame('Foo', $objects[0]->name);
		$this->assertSame(1, $objects[0]->id);
	}

	public function testIteratorYieldsRows(): void {
		$db = new MockDatabase();
		$db->addToDQLStack([
			['id' => 1],
			['id' => 2],
		]);

		$rows = [];
		foreach($db->select() as $row) {
			$rows[] = $row;
		}
		$this->assertSame([['id' => 1], ['id' => 2]], $rows);
	}

	public function testDmlStackIsUsedForExecAndPrepare(): void {
		$db = new MockDatabase();
		$db->addToDMLStack(1);
		$db->addToDMLStack(2);

		$this->assertSame(1, $db->insert()->into('table')->add('name', 'X')->run());

		$prepared = $db->insert()->into('table')->add('name', 'Y')->prepare();
		$this->assertSame(2, $prepared->run());
	}

	public function testDdlStackIsUsedForExec(): void {
		$db = new MockDatabase();
		$db->addToDDLStack(3);

		$this->assertSame(3, $db->exec('CREATE TABLE foo (id INT)'));
	}

	public function testQueryReturnsExecutedStatement(): void {
		$db = new MockDatabase();
		$db->addToDQLStack([
			['id' => 10],
		]);

		$stmt = $db->query('SELECT * FROM table');
		$this->assertSame([['id' => 10]], $stmt->fetchAll());
	}
}
