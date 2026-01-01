<?php

namespace Kir\MySQL\Builder;

use BadMethodCallException;
use Kir\MySQL\Builder\Internal\DDLPreparable;
use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Builder\Traits\CreateDDLRunnable;

/**
 * @implements DDLPreparable<int>
 */
class RunnableInsert extends Insert implements DDLPreparable {
	/** @use CreateDDLRunnable<int> */
	use CreateDDLRunnable;

	/**
	 * @inheritDoc
	 */
	public function insertRows(iterable $rows) {
		if(!is_iterable($rows)) {
			throw new BadMethodCallException('Expected $rows to by an iterable');
		}
		$result = [];
		$query = $this->__toString();
		$stmt = $this->db()->prepare($query);
		foreach($rows as $row) {
			$stmt->execute($row);
			$result[] = (int) $this->db()->getLastInsertId();
		}
		$stmt->closeCursor();

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function run(array $params = []): int {
		return $this->prepare()->run($params);
	}

	/**
	 * @return DDLRunnable<int>
	 */
	public function prepare(): DDLRunnable {
		return $this->createPreparable(
			$this->db()->prepare($this),
			fn() => (int) $this->db()->getLastInsertId()
		);
	}
}
