<?php
namespace Kir\MySQL\Builder\UpdateTest;

use Kir\MySQL\Builder\Update;
use Kir\MySQL\Databases\TestDB;

class TestUpdate extends Update {
	/**
	 * @return TestUpdate
	 */
	public static function create() {
		$db = new TestDB();
		return new static($db);
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
