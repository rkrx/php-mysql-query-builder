<?php
namespace Kir\MySQL\Builder\UpdateTest;

use Kir\MySQL\Builder\Update;
use Kir\MySQL\Databases\Mock;

class TestUpdate extends Update {
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