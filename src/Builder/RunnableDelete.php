<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DDLPreparable;
use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Builder\Traits\CreateDDLRunnable;

/**
 * @implements DDLPreparable<int>
 */
class RunnableDelete extends Delete implements DDLPreparable {
	/** @use CreateDDLRunnable<int> */
	use CreateDDLRunnable;

	/**
	 * @param array<string, mixed> $params
	 * @return int
	 */
	public function run(array $params = []) {
		return $this->prepare()->run($params);
	}

	/**
	 * @return DDLRunnable<int>
	 */
	public function prepare(): DDLRunnable {
		return $this->createPreparable(
			$this->db()->prepare($this),
			fn($v) => (int) $v
		);
	}
}
