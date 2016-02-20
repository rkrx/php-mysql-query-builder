<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Traits\GroupByBuilder;
use Kir\MySQL\Builder\Traits\HavingBuilder;
use Kir\MySQL\Builder\Traits\OffsetBuilder;
use Kir\MySQL\Builder\Traits\OrderByBuilder;
use Kir\MySQL\Builder\Traits\TableBuilder;
use Kir\MySQL\Builder\Traits\JoinBuilder;
use Kir\MySQL\Builder\Traits\LimitBuilder;
use Kir\MySQL\Builder\Traits\TableNameBuilder;
use Kir\MySQL\Builder\Traits\UnionBuilder;
use Kir\MySQL\Builder\Traits\WhereBuilder;

class Select extends Statement {
	use TableNameBuilder;
	use TableBuilder;
	use JoinBuilder;
	use WhereBuilder;
	use HavingBuilder;
	use GroupByBuilder;
	use OrderByBuilder;
	use LimitBuilder;
	use OffsetBuilder;
	use UnionBuilder;

	/** @var string[] */
	private $fields = array();
	/** @var bool */
	private $calcFoundRows = false;
	/** @var bool */
	private $forUpdate = false;
	/** @var bool */
	private $distinct = false;

	/**
	 * @param bool $distinct
	 * @return $this
	 */
	public function distinct($distinct = true) {
		$this->distinct = $distinct;
		return $this;
	}

	/**
	 * @param string $expression
	 * @param string $alias
	 * @return $this
	 */
	public function field($expression, $alias = null) {
		if (is_object($expression)) {
			$expression = (string) $expression;
			$expression = trim($expression);
			$expression = rtrim($expression, ';');
			$expression = trim($expression);
			$lines = explode("\n", $expression);
			$lines = array_map(function($line) { return "\t\t{$line}"; }, $lines);
			$expression = join("\n", $lines);
			$expression = sprintf("(\n%s\n\t)", $expression);
		}
		if ($alias === null) {
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
		foreach ($fields as $alias => $expression) {
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
		if (ini_get("mysql.trace_mode")) {
			throw new \Exception('This function cant operate with mysql.trace_mode is set.');
		}
		$this->calcFoundRows = $calcFoundRows;
		return $this;
	}

	/**
	 * @param string $alias
	 * @param string $tableName
	 * @return $this
	 */
	public function from($alias, $tableName = null) {
		$this->addTable($alias, $tableName);
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$query = "SELECT";
		if ($this->calcFoundRows) {
			$query .= " SQL_CALC_FOUND_ROWS";
		}
		if ($this->distinct) {
			$query .= " DISTINCT";
		}
		$query .= "\n";
		$query = $this->buildFields($query);
		if (count($this->getTables())) {
			$query .= "FROM\n";
		}
		$query = $this->buildTables($query);
		$query = $this->buildJoins($query);
		$query = $this->buildWhereConditions($query);
		$query = $this->buildGroups($query);
		$query = $this->buildHavingConditions($query);
		$query = $this->buildOrder($query);
		$query = $this->buildLimit($query);
		$query = $this->buildOffset($query);
		$query = $this->buildUnions($query);
		$query = $this->buildForUpdate($query);
		return $query;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildFields($query) {
		$fields = array();
		if (count($this->fields)) {
			foreach ($this->fields as $alias => $expression) {
				if (is_numeric($alias)) {
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
	private function buildForUpdate($query) {
		if ($this->forUpdate) {
			$query .= "FOR UPDATE\n";
		}
		return $query;
	}
}
