<?php
namespace Kir\MySQL\Builder;

class RunnableDelete extends Delete {
	/**
	 * @return int
	 */
	public function run() {
		$query = (string)$this;
		return $this->mysql()->exec($query);
	}
}