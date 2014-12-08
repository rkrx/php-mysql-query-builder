<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Tools\AliasReplacer;

class Select extends Statement {
	/**
	 * @var string[]
	 */
	private $fields;

	/**
	 * @var array[]
	 */
	private $tables = array();

	/**
	 * @var array
	 */
	private $orderBy = array();

	/**
	 * @var array
	 */
	private $groupBy = array();

	/**
	 * @var array
	 */
	private $where = array();

	/**
	 * @var array
	 */
	private $having = array();

	/**
	 * @var int
	 */
	private $limit = null;

	/**
	 * @var int
	 */
	private $offset = null;

	/**
	 * @var bool
	 */
	private $calcFoundRows = false;

	/**
	 * @var bool
	 */
	private $forUpdate = false;

	/**
	 * @param string $expression
	 * @param string $alias
	 * @return $this
	 */
	public function field($expression, $alias = null) {
		if(is_object($expression)) {
			$expression = (string)$expression;
		}
		if($alias === null) {
			$this->fields[] = $expression;
		} else {
			$this->fields[$alias] = $expression;
		}
		return $this;
	}

	/**
	 * @param array $fields
	 * @return $this
	 */
	public function fields(array $fields) {
		foreach($fields as $alias => $expression) {
			$this->field($expression, $alias);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param string $alias
	 * @param string $table
	 * @return $this
	 */
	public function from($alias, $table) {
		return $this->addTable('FROM', $alias, $table);
	}

	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @return $this
	 */
	public function joinInner($alias, $table, $expression = null) {
		$arguments = array_slice(func_get_args(), 3);
		return $this->addTable('INNER', $alias, $table, $expression, $arguments);
	}

	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @return $this
	 */
	public function joinLeft($alias, $table, $expression) {
		$arguments = array_slice(func_get_args(), 3);
		return $this->addTable('LEFT', $alias, $table, $expression, $arguments);
	}

	/**
	 * @param string $alias
	 * @param string $table
	 * @param string $expression
	 * @return $this
	 */
	public function joinRight($alias, $table, $expression) {
		$arguments = array_slice(func_get_args(), 3);
		return $this->addTable('RIGHT', $alias, $table, $expression, $arguments);
	}

	/**
	 * @param string $expression
	 * @return $this
	 */
	public function where($expression) {
		$this->where[] = array($expression, array_slice(func_get_args(), 1));
		return $this;
	}

	/**
	 * @param string $expression
	 * @return $this
	 */
	public function having($expression) {
		$this->having[] = array($expression, array_slice(func_get_args(), 1));
		return $this;
	}

	/**
	 * @param string $expression
	 * @param string $direction
	 * @return $this
	 */
	public function orderBy($expression, $direction = 'asc') {
		if(strtolower($direction) != 'desc') {
			$direction = 'ASC';
		}
		if(is_array($expression)) {
			if(!count($expression)) {
				return $this;
			}
			$arguments = array(
				$expression[0],
				array_slice($expression, 1)
			);
			$expression = call_user_func_array(array($this->db(), 'quoteExpression'), $arguments);
		}
		$this->orderBy[] = array($expression, $direction);
		return $this;
	}

	/**
	 * @param string $expression
	 * @return $this
	 */
	public function groupBy($expression) {
		foreach(func_get_args() as $expression) {
			if(is_array($expression)) {
				if(!count($expression)) {
					continue;
				}
				$arguments = array(
					$expression[0],
					array_slice($expression, 1)
				);
				$expression = call_user_func_array(array($this->db(), 'quoteExpression'), $arguments);
			}
			$this->groupBy[] = $expression;
		}
		return $this;
	}

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param int $offset
	 * @return $this
	 */
	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @param bool $enabled
	 * @return $this
	 */
	public function forUpdate($enabled = true) {
		$this->forUpdate = $enabled;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getCalcFoundRows() {
		return $this->calcFoundRows;
	}

	/**
	 * @param bool $calcFoundRows
	 * @throws \Exception
	 * @return $this
	 */
	public function setCalcFoundRows($calcFoundRows = true) {
		if(ini_get("mysql.trace_mode")) {
			throw new \Exception('This function cant operate with mysql.trace_mode is set.');
		}
		$this->calcFoundRows = $calcFoundRows;
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$query = "SELECT";
		if($this->calcFoundRows) {
			$query .= " SQL_CALC_FOUND_ROWS";
		}
		$query .= "\n";
		$query = $this->buildFields($query);
		$query = $this->buildFrom($query);
		$query = $this->buildJoins($query);
		$query = $this->buildConditions('WHERE', $this->where, $query);
		$query = $this->buildGroups($query);
		$query = $this->buildConditions('HAVING', $this->having, $query);
		$query = $this->buildOrder($query);
		$query = $this->buildLimit($query);
		$query = $this->buildForUpdate($query);
		$query .= ";\n";
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
	private function addTable($type, $alias, $name, $expression = null, array $arguments = array()) {
		$this->tables[] = array(
			'type' => $type,
			'alias' => $alias,
			'name' => $name,
			'expression' => $expression,
			'arguments' => $arguments
		);
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildFields($query) {
		$fields = array();
		if(count($this->fields)) {
			foreach($this->fields as $alias => $expression) {
				if(is_numeric($alias)) {
					$fields[] = "\t{$expression}";
				} else {
					$fields[] = "\t{$expression} AS `{$alias}`";
				}
			}
		} else {
			$fields[] = "\t*";
		}
		return $query.join(",\n", $fields)."\n";
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildFrom($query) {
		$arr = array();
		foreach($this->tables as $table) {
			if($table['type'] == 'FROM') {
				$arr[] = "\t".$this->buildTableName($table['alias'], $table['name']);
			}
		}
		if(count($arr)) {
			$query .= "FROM\n";
			$query .= join(",\n", $arr)."\n";
		}
		return $query;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildJoins($query) {
		$arr = array();
		foreach($this->tables as $table) {
			if($table['type'] != 'FROM') {
				$join = $table['type']." JOIN\n";
				$join .= "\t".$this->buildTableName($table['alias'], $table['name']);
				if($table['expression']) {
					$join .= " ON ".$this->buildExpression($table['expression'], $table['arguments']);
				}
				$arr[] = $join;
			}
		}
		if(count($arr)) {
			$query .= join("\n", $arr)."\n";
		}
		return $query;
	}

	/**
	 * @param string $type
	 * @param string[] $conditions
	 * @param string $query
	 * @return string
	 */
	private function buildConditions($type, array $conditions, $query) {
		if(!count($conditions)) {
			return $query;
		}
		$query .= "{$type}\n";
		$arr = array();
		foreach($conditions as $condition) {
			list($expression, $arguments) = $condition;
			$expr = $this->db()->quoteExpression($expression, $arguments);
			$arr[] = "\t({$expr})";
		}
		$query .= join("\n\tAND\n", $arr);
		return $query."\n";
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildOrder($query) {
		if(!count($this->orderBy)) {
			return $query;
		}
		$query .= "ORDER BY\n";
		$arr = array();
		foreach($this->orderBy as $order) {
			list($expression, $direction) = $order;
			$arr[] = sprintf("\t%s %s", $expression, strtoupper($direction));
		}
		return $query.join(",\n", $arr)."\n";
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildGroups($query) {
		if(!count($this->groupBy)) {
			return $query;
		}
		$query .= "GROUP BY\n";
		$arr = array();
		foreach($this->groupBy as $expression) {
			$arr[] = "\t{$expression}";
		}
		return $query.join(",\n", $arr)."\n";
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildLimit($query) {
		if($this->limit === null) {
			return $query;
		}
		$query .= "LIMIT\n\t{$this->limit}\n";
		if($this->offset !== null) {
			$query .= "OFFSET\n\t{$this->offset}\n";
		}
		return $query;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildForUpdate($query) {
		if($this->forUpdate) {
			$query .= "FOR UPDATE\n";
		}
		return $query;
	}

	/**
	 * @param string $alias
	 * @param string $name
	 * @return string
	 */
	private function buildTableName($alias, $name) {
		if(is_object($name)) {
			$name = (string) $name;
			$lines = explode("\n", $name);
			foreach($lines as &$line) {
				$line = "\t{$line}";
			}
			$name = join("\n", $lines);
			$name = '(' . trim(rtrim(trim($name), ';')) . ')';
		}
		$name = (new AliasReplacer($this->db()->getAliasRegistry()))->replace($name);
		return sprintf("%s %s", $name, $alias);
	}

	/**
	 * @param string $expression
	 * @param array $arguments
	 * @return string
	 */
	private function buildExpression($expression, array $arguments) {
		return $this->db()->quoteExpression($expression, $arguments);
	}
}