<?php
namespace Kir\MySQL\Builder;

class RunnableDelete extends Delete {
	/**
	 * @param array $params
	 * @return int
	 */
	public function run(array $params = array()) {
		$query = (string)$this;
		return $this->db()->exec($query, $params);
	}
}
