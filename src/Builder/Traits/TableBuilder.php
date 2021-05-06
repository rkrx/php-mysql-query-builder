<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Select;
use Kir\MySQL\Tools\VirtualTable;

trait TableBuilder {
	use AbstractTableNameBuilder;

	/** @var array<int, array{alias: string|null, name: string|Select|VirtualTable|array<int, null|int|float|string|array<string, mixed>>}> */
	private $tables = [];

	/**
	 * @param string|null $alias
	 * @param string|Select|VirtualTable|array<int, null|int|float|string|array<string, mixed>> $table
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
	 * @return array<int, array{alias: string|null, name: string|array<int, null|int|float|string|Select|VirtualTable|array<string, mixed>>}>
	 */
	protected function getTables(): array {
		return $this->tables;
	}
}
