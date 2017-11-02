<?php
namespace Kir\MySQL\Tools;

use Kir\MySQL\Builder\Select;

class VirtualTables {
	/** @var Select[] */
	private $virtualTables = [];
	
	/**
	 * @param string $tableName
	 * @param Select|\Closure $select
	 * @return $this
	 */
	public function add($tableName, $select) {
		$this->virtualTables[$tableName] = $select;
		return $this;
	}
	
	/**
	 * @param string|VirtualTable $tableName
	 * @return bool
	 */
	public function has($tableName) {
		return array_key_exists((string) $tableName, $this->virtualTables);
	}
	
	/**
	 * @param string $tableName
	 * @return Select|null
	 */
	public function get($tableName) {
		if($this->has($tableName)) {
			$table = $this->virtualTables[(string) $tableName];
			if($table instanceof \Closure) {
				$params = [];
				if($tableName instanceof VirtualTable) {
					$params = $tableName->getParams();
				}
				return call_user_func($table, $params);
			}
			return $table;
		}
		return null;
	}
}
