<?php
namespace Kir\MySQL\Builder\Traits;

trait JoinBuilder {
	use AbstractDB;
	use AbstractTableNameBuilder;

	/**
	 * @var array[]
	 */
	private $joinTables = array();

	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @return $this
	 */
	public function joinInner($alias, $table, $expression = null) {
		$arguments = array_slice(func_get_args(), 3);
		return $this->addJoin('INNER', $alias, $table, $expression, $arguments);
	}

	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @return $this
	 */
	public function joinLeft($alias, $table, $expression) {
		$arguments = array_slice(func_get_args(), 3);
		return $this->addJoin('LEFT', $alias, $table, $expression, $arguments);
	}

	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @return $this
	 */
	public function joinRight($alias, $table, $expression) {
		$arguments = array_slice(func_get_args(), 3);
		return $this->addJoin('RIGHT', $alias, $table, $expression, $arguments);
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildJoins($query) {
		$arr = array();
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
	 * @param array $arguments
	 * @return $this
	 */
	private function addJoin($type, $alias, $name, $expression = null, array $arguments = array()) {
		$this->joinTables[] = array(
			'type' => $type,
			'alias' => $alias,
			'name' => $name,
			'expression' => $expression,
			'arguments' => $arguments
		);
		return $this;
	}
}