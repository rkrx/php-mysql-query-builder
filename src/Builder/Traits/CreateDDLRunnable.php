<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Database\DatabaseStatement;

trait CreateDDLRunnable {
	/**
	 * @param DatabaseStatement $query
	 * @param callback $callbackFn
	 * @return DDLRunnable
	 */
	public function createPreparable(DatabaseStatement $query, callable $callbackFn = null) {
		$runnable = new DDLRunnable($query, $callbackFn);
		return $runnable;
	}
}
