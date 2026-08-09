<?php

namespace Kir\MySQL\Databases;

use Kir\MySQL\Databases\Mock\MockDatabase;
use Kir\MySQL\Databases\Mock\MockRunnableSelect;
use Kir\MySQL\Exceptions\StatementPreparationException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class MySQLPrepareTest extends TestCase {
	public function testPreparedSelectBuildsAndPreparesQueryOnlyOnce(): void {
		$db = new class extends MockDatabase {
			public int $prepareCount = 0;

			public function prepare(string $query) {
				$this->prepareCount++;

				return parent::prepare($query);
			}
		};
		$db->addToDQLStack([['value' => 1]]);
		$db->addToDQLStack([['value' => 2]]);
		$select = new class($db) extends MockRunnableSelect {
			public int $renderCount = 0;

			public function __toString(): string {
				$this->renderCount++;

				return parent::__toString();
			}
		};

		$prepared = $select->field(':value', 'value')->prepare();

		$this->assertSame(1, $select->renderCount);
		$this->assertSame(1, $db->prepareCount);
		$this->assertSame([1], $prepared->bindValue('value', 1)->fetchValues());
		$this->assertSame([2], $prepared->bindValue('value', 2)->fetchValues());
		$this->assertSame(1, $select->renderCount);
		$this->assertSame(1, $db->prepareCount);
	}

	public function testPrepareFalseThrowsSpecificException(): void {
		$pdo = new class extends PDO {
			public function __construct() {}

			public function getAttribute(int $attribute): mixed {
				return $attribute === PDO::ATTR_ERRMODE ? PDO::ERRMODE_EXCEPTION : null;
			}

			/** @param array<mixed, mixed> $options */
			public function prepare(string $query, array $options = []): PDOStatement|false {
				return false;
			}
		};

		$db = new MySQL($pdo);

		$this->expectException(StatementPreparationException::class);
		$this->expectExceptionMessage('Could not prepare statement');

		$db->select()->prepare();
	}
}
