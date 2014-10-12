<?php
namespace Kir\MySQL\Builder;

class RunnableInsert extends Insert {
	/**
	 */
	public function run() {
		$query = $this->__toString();
		$this->db()->exec($query);
		return (int) $this->db()->getLastInsertId();
	}
}