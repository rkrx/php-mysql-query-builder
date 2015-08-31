<?php
namespace Kir\MySQL\Builder;

use BadMethodCallException;
use Traversable;

class RunnableInsert extends Insert {
	/**
	 * @param array|Traversable $rows
	 * @return int[] Insert IDs
	 */
	public function insertRows($rows) {
		if(!(is_array($rows) || $rows instanceof Traversable)) {
			throw new BadMethodCallException('Expected $rows to by an array or an instance of \\Traversable');
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
	 * @param array $params
	 * @return int
	 * @throws Exception
	 */
	public function run(array $params = array()) {
		$query = $this->__toString();
		$this->db()->exec($query, $params);
		return (int) $this->db()->getLastInsertId();
	}
}
