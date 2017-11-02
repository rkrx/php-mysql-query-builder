<?php
namespace Kir\MySQL\Builder\UpdateTest;

use Kir\MySQL\Builder\Update;
use Kir\MySQL\Databases\TestDB;

class TestUpdate extends Update {
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
