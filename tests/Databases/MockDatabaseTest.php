<?php
namespace Kir\MySQL\Databases;

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
