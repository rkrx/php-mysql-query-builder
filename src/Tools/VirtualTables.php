<?php
namespace Kir\MySQL\Tools;

use Closure;
use Kir\MySQL\Builder\Select;

class VirtualTables {
	/** @var array<string, Closure|Select> */
	private array $virtualTables = [];

	/**
	 * @param string $tableName
	 * @param Select|Closure $select
	 * @return $this
	 */
	public function add(string $tableName, $select) {
		$this->virtualTables[$tableName] = $select;
		return $this;
	}

	/**
	 * @param string|VirtualTable $tableName
	 * @return bool
	 */
	public function has($tableName): bool {
		return array_key_exists((string) $tableName, $this->virtualTables);
	}

	/**
	 * @param string|VirtualTable $tableName
	 * @return Select|null
	 */
	public function get($tableName): ?Select {
		if($this->has($tableName)) {
			$table = $this->virtualTables[(string) $tableName];
			if($table instanceof Closure) {
				if($tableName instanceof VirtualTable) {
					$params = $tableName->getParams();
					return $table($params);
				}
				return $table();
			}
			return $table;
		}
		return null;
	}
}
