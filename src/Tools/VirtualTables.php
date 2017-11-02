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
	 * @param string $tableName
	 * @return bool
	 */
	public function has($tableName) {
		return array_key_exists($tableName, $this->virtualTables);
	}
	
	/**
	 * @param string $tableName
	 * @return Select|null
	 */
	public function get($tableName) {
		if($this->has($tableName)) {
			$table = $this->virtualTables[$tableName];
			if($table instanceof \Closure) {
				return call_user_func($table);
			}
			return $table;
		}
		return null;
	}
}
