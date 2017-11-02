<?php
namespace Kir\MySQL\Builder\SelectTest;

use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Database;
use Kir\MySQL\Databases\TestDB;

class TestSelect extends RunnableSelect {
	/**
	 * @param Database|null $db
	 * @return $this
	 */
	public static function create(Database $db = null) {
		if($db === null) {
			$db = new TestDB();
		}
		return new static($db);
	}

	/**
	 * @return string
	 */
	public function asString() {
		return $this->__toString();
	}
}
