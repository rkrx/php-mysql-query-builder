<?php
namespace Kir\MySQL\Builder\DeleteTest;

use Kir\MySQL\Builder\Delete;
use Kir\MySQL\Databases\TestDB;

class TestDelete extends Delete {
	/**
	 * @return TestDelete
	 */
	public static function create(): TestDelete {
		$db = new TestDB();
		return new static($db);
	}

	public function run(array $params = []) {
		return 0;
	}

	/**
	 * @return string
	 */
	public function asString() {
		return $this->__toString();
	}
}
