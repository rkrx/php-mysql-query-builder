<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Internal\Types;

/**
 * @phpstan-import-type DBParameterValueType from Types
 * @phpstan-import-type DBTableNameType from Types
 */
trait JoinBuilder {
	use AbstractDB;
	use AbstractTableNameBuilder;

	/** @var array<int, array{type: string, alias: string, name: DBTableNameType, expression: string|null, arguments: list<DBParameterValueType>}> */
	private array $joinTables = [];

	/**
	 * @param string $alias
	 * @param DBTableNameType $table
	 * @param string|null $expression
	 * @param DBParameterValueType ...$args
	 * @return $this
	 */
	public function joinInner(string $alias, $table, ?string $expression = null, ...$args) {
		return $this->addJoin('INNER', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $alias
	 * @param DBTableNameType $table
	 * @param string $expression
	 * @param DBParameterValueType ...$args
	 * @return $this
	 */
	public function joinLeft(string $alias, $table, string $expression, ...$args) {
		return $this->addJoin('LEFT', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $alias
	 * @param DBTableNameType $table
	 * @param string $expression
	 * @param DBParameterValueType ...$args
	 * @return $this
	 */
	public function joinRight(string $alias, $table, string $expression, ...$args) {
		return $this->addJoin('RIGHT', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildJoins(string $query): string {
		$arr = [];
		foreach($this->joinTables as $table) {
			$join = $table['type']." JOIN\n";
			$join .= "\t" . $this->buildTableName($table['alias'], $table['name']);
			if($table['expression']) {
				$join .= " ON " . $this->db()->quoteExpression($table['expression'], $table['arguments']);
			}
			$arr[] = $join;
		}
		if(count($arr)) {
			$query .= implode("\n", $arr)."\n";
		}
		return $query;
	}

	/**
	 * @param string $type
	 * @param string $alias
	 * @param DBTableNameType $name
	 * @param string|null $expression
	 * @param array<DBParameterValueType> $arguments
	 * @return $this
	 */
	private function addJoin(string $type, string $alias, $name, ?string $expression = null, array $arguments = []) {
		$this->joinTables[] = [
			'type' => $type,
			'alias' => $alias,
			'name' => $name,
			'expression' => $expression,
			'arguments' => $arguments
		];
		return $this;
	}
}
