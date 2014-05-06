<?php
namespace Kir\MySQL\Builder;

class RunnableUpdate extends Update {
	/**
	 * @return int
	 */
	public function run() {
		$query = $this->__toString();
		return $this->mysql()->exec($query);
	}
}