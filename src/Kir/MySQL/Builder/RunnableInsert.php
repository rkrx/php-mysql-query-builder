<?php
namespace Kir\MySQL\Builder;

class RunnableInsert extends Insert {
	/**
	 */
	public function run() {
		$query = $this->__toString();
		$this->mysql()->exec($query);
		return (int) $this->mysql()->getLastInsertId();
	}
}