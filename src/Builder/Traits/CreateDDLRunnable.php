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
	 * @param callable(scalar): T $callbackFn
	 * @return DDLRunnable<T>
	 */
	public function createPreparable(DatabaseStatement $query, $callbackFn): DDLRunnable {
		// @phpstan-ignore-next-line
		return new DDLRunnable($query, $callbackFn);
	}
}
