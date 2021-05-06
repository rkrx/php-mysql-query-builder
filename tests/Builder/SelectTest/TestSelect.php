<?php
namespace Kir\MySQL\Builder\SelectTest;

use Kir\MySQL\Databases\MySQL\MySQLRunnableSelect;
use Kir\MySQL\Databases\MySQL;
use Kir\MySQL\Databases\TestDB;

class TestSelect extends MySQLRunnableSelect {
	/**
	 * @param MySQL|null $db
	 * @return TestSelect
	 */
	public static function create(MySQL $db = null): TestSelect {
		if($db === null) {
			$db = new TestDB();
		}
		return new static($db);
	}

	/**
	 * @return string
	 */
	public function asString(): string {
		return $this->__toString();
	}
}
