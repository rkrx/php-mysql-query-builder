<?php
namespace Kir\MySQL\Builder\DeleteTest;

use Kir\MySQL\Builder\Delete;
use Kir\MySQL\Databases\Mock;

class TestDelete extends Delete {
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