<?php
namespace Kir\MySQL\Tools;

use Kir\MySQL\Builder\Select;

class VirtualTables {
	/** @var Select[] */
	private $virtualTables = [];
	
	/**
	 * @param string $tableName
	 * @param Select $select
	 * @return $this
	 */
	public function add($tableName, Select $select) {
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
			return $this->virtualTables[$tableName];
		}
		return null;
	}
}
