<?php
namespace Kir\MySQL\Builder\SelectTest;

use Kir\MySQL\Builder\Select;
use Kir\MySQL\Databases\Mock;

class TestSelect extends Select {
	/**
	 * @return $this
	 */
	public static function create() {
		$functions = array(
			'getTableFields' => function ($tableName) {
				return array();
			}
		);

		$db = new Mock($functions);
		return new static($db);
	}

	/**
	 * @return string
	 */
	public function asString() {
		$str = $this->__toString();
		return trim(preg_replace('/\\s+/', ' ', $str));
	}
}