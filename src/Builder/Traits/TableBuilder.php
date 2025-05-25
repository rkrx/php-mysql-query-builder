<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Internal\Types;

/**
 * @phpstan-import-type DBTableNameType from Types
 */
trait TableBuilder {
	use AbstractTableNameBuilder;

	/** @var array<int, array{alias: string|null, name: DBTableNameType}> */
	private $tables = [];

	/**
	 * @param string|null $alias
	 * @param DBTableNameType $table
	 * @return $this
	 */
	protected function addTable(?string $alias, $table) {
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
	 * @return array<int, array{alias: string|null, name: DBTableNameType}>
	 */
	protected function getTables(): array {
		return $this->tables;
	}
}
