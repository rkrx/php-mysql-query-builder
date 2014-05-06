<?php
namespace Kir\MySQL\Builder\SelectTest;

use Kir\MySQL\Builder\Select;
use Kir\MySQL\MySQL;
use PDO;

class TestSelect extends Select {
	/**
	 * @return $this
	 */
	public static function create() {
		$mysql = new MySQL(new PDO('sqlite::memory:'));
		return new static($mysql);
	}

	/**
	 * @return string
	 */
	public function asString() {
		$str = $this->__toString();
		return trim(preg_replace('/\\s+/', ' ', $str));
	}
}