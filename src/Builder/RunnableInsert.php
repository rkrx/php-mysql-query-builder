<?php
namespace Kir\MySQL\Builder;

class RunnableInsert extends Insert {
	/**
	 * @param array $rows
	 * @return int[] Insert IDs
	 */
	public function insertRows(array $rows) {
		$result = [];
		$query = $this->__toString();
		$stmt = $this->db()->prepare($query);
		foreach($rows as $row) {
			$stmt->execute($row);
			$result[] = (int) $this->db()->getLastInsertId();
		}
		return $result;
	}

	/**
	 */
	public function run() {
		$query = $this->__toString();
		$this->db()->exec($query);
		return (int) $this->db()->getLastInsertId();
	}
}