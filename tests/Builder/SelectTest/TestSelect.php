<?php
namespace Kir\MySQL\Builder\SelectTest;

use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\TestDB;

class TestSelect extends RunnableSelect {
	/**
	 * @return $this
	 */
	public static function create() {
		$db = new TestDB();
		return new static($db);
	}

	/**
	 * @return string
	 */
	public function asString() {
		return $this->__toString();
	}
}