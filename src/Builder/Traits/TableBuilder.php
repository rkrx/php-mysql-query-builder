<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Select;

trait TableBuilder {
	use AbstractTableNameBuilder;

	/** @var array[] */
	private $tables = [];

	/**
	 * @param string|array|Select $alias
	 * @param string|Select|null $table
	 * @return $this
	 */
	protected function addTable($alias, $table = null) {
		if($table === null) {
			list($alias, $table) = [$table, $alias];
		}
		$this->tables[] = ['alias' => $alias, 'name' => $table];
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildTables(string $query): string {
		$arr = [];
		foreach($this->tables as $table) {
			$arr[] = "\t".$this->buildTableName($table['alias'], $table['name']);
		}
		if(count($arr)) {
			$query .= implode(",\n", $arr)."\n";
		}
		return $query;
	}

	/**
	 * @return array[]
	 */
	protected function getTables(): array {
		return $this->tables;
	}
}
