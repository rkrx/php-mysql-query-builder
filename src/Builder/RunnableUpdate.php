<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DDLPreparable;
use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Builder\Traits\CreateDDLRunnable;

/**
 * @implements DDLPreparable<int>
 */
class RunnableUpdate extends Update implements DDLPreparable {
	/** @use CreateDDLRunnable<int> */
	use CreateDDLRunnable;

	/**
	 * @inheritDoc
	 */
	public function run(array $params = []): int {
		$query = $this->__toString();
		return $this->db()->exec($query, $params);
	}

	/**
	 * @return DDLRunnable<int>
	 */
	public function prepare(): DDLRunnable {
		return $this->createPreparable($this->db()->prepare($this));
	}
}
