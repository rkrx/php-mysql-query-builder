<?php
namespace Kir\MySQL\Builder\UpdateTest;

use Kir\MySQL\Builder\Update;
use Kir\MySQL\Databases\TestDB;

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

		$db = new TestDB($functions);
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