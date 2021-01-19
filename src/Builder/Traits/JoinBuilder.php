<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Select;

trait JoinBuilder {
	use AbstractDB;
	use AbstractTableNameBuilder;

	/** @var array[] */
	private $joinTables = [];

	/**
	 * @param string $alias
	 * @param string|array[]|Select $table
	 * @param string|null $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function joinInner(string $alias, $table, ?string $expression = null, ...$args) {
		return $this->addJoin('INNER', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $alias
	 * @param string|array[]|Select $table
	 * @param string $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function joinLeft(string $alias, $table, string $expression, ...$args) {
		return $this->addJoin('LEFT', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $alias
	 * @param string|array[]|Select $table
	 * @param string $expression
	 * @param array<int, mixed> $args
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
	 * @param string|array[] $name
	 * @param string|null $expression
	 * @param array<int, mixed> $arguments
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
