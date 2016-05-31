<?php
namespace Kir\MySQL\Builder\SelectTest;

use Kir\MySQL\Builder\Extensions\CondHaving;
use Kir\MySQL\Builder\Extensions\CondWhere;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\TestDB;

class TestSelect extends RunnableSelect {
	/**
	 * @return $this
	 */
	public static function create() {
		$db = new TestDB();
		$db->getExtensionMethodRegistry()->add('whereCond', function ($queryBuilder) {
			return new CondWhere($queryBuilder);
		});
		$db->getExtensionMethodRegistry()->add('havingCond', function ($queryBuilder) {
			return new CondHaving($queryBuilder);
		});
		return new static($db);
	}

	/**
	 * @return string
	 */
	public function asString() {
		return $this->__toString();
	}
}
