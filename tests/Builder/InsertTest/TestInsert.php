<?php
namespace Kir\MySQL\Builder\InsertTest;

use Kir\MySQL\Builder\Insert;
use Kir\MySQL\Databases\Mock;

class TestInsert extends Insert {
	/**
	 * @return static
	 */
	public static function create() {
		$functions = array(
			'getTableFields' => function ($tableName) {
				return array(
					'id',
					'name',
					'last_update'
				);
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