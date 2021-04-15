<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Database\DatabaseStatement;

/**
 * @template T
 */
trait CreateDDLRunnable {
	/**
	 * @param DatabaseStatement $query
	 * @param null|callable(): T $callbackFn
	 * @return DDLRunnable<T>
	 */
	public function createPreparable(DatabaseStatement $query, callable $callbackFn = null): DDLRunnable {
		return new DDLRunnable($query, $callbackFn);
	}
}
