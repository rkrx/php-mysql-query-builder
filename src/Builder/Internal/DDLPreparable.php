<?php
namespace Kir\MySQL\Builder\Internal;

/**
 * @template T
 */
interface DDLPreparable {
	/**
	 * @return DDLRunnable<T>
	 */
	public function prepare(): DDLRunnable;
}
