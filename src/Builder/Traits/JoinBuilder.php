<?php
namespace Kir\MySQL\Builder\Traits;

trait JoinBuilder {
	use AbstractDB;
	use AbstractTableNameBuilder;

	/** @var array[] */
	private $joinTables = [];
	
	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function joinInner($alias, $table, $expression = null, ...$args) {
		return $this->addJoin('INNER', $alias, $table, $expression, $args);
	}
	
	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function joinLeft($alias, $table, $expression, ...$args) {
		return $this->addJoin('LEFT', $alias, $table, $expression, $args);
	}
	
	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function joinRight($alias, $table, $expression, ...$args) {
		return $this->addJoin('RIGHT', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildJoins($query) {
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
			$query .= join("\n", $arr)."\n";
		}
		return $query;
	}

	/**
	 * @param string $type
	 * @param string $alias
	 * @param string $name
	 * @param string $expression
	 * @param array<int, mixed> $arguments
	 * @return $this
	 */
	private function addJoin($type, $alias, $name, $expression = null, array $arguments = []) {
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
