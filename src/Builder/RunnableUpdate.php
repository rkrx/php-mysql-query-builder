<?php
namespace Kir\MySQL\Builder;

class RunnableUpdate extends Update {
	/**
	 * @param array $params
	 * @return int
	 */
	public function run(array $params = array()) {
		$query = $this->__toString();
		return $this->db()->exec($query, $params);
	}
}