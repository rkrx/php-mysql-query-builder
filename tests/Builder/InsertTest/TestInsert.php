<?php
namespace Kir\MySQL\Builder\InsertTest;

use Kir\MySQL\Builder\Insert;
use Kir\MySQL\Databases\TestDB;

class TestInsert extends Insert {
	/**
	 * @return TestInsert
	 */
	public static function create(): TestInsert {
		$db = new TestDB();
		return new static($db);
	}

	/**
	 * @inheritDoc
	 */
	public function insertRows($rows) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function run(array $params = []): int {
		return 0;
	}

	/**
	 * @return string
	 */
	public function asString(): string {
		return $this->__toString();
	}
}
