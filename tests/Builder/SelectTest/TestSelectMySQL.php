<?php
namespace Kir\MySQL\Builder\SelectTest;

use Kir\MySQL\Databases\MySQL\MySQLRunnableSelect;
use Kir\MySQL\Databases\MySQL;
use Kir\MySQL\Databases\TestDB;

class TestSelectMySQL extends MySQLRunnableSelect {
	/**
	 * @param MySQL|null $db
	 * @return TestSelectMySQL
	 */
	public static function create(MySQL $db = null): TestSelectMySQL {
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
