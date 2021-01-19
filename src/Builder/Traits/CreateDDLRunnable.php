<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Database\DatabaseStatement;

trait CreateDDLRunnable {
	/**
	 * @param DatabaseStatement $query
	 * @param callable|null $callbackFn
	 * @return DDLRunnable
	 */
	public function createPreparable(DatabaseStatement $query, callable $callbackFn = null): DDLRunnable {
		return new DDLRunnable($query, $callbackFn);
	}
}
