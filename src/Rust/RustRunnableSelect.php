<?php

namespace Kir\MySQL\Rust;

use Kir\MySQL\Builder\Expr\OrderBySpecification;
use Kir\MySQL\Builder\RunnableTemporaryTable;
use Kir\MySQL\Builder\Select;
use Kir\MySQL\Databases\MySQL;
use Kir\MySQL\Databases\MySQL\MySQLRunnableSelect;

class RustRunnableSelect extends MySQLRunnableSelect {
	use NativeStatementSupport;

	/** @var object */
	private $native;
	/** @var array<int|string, string> */
	private array $fields = [];
	/** @var array<int, array{string, string}> */
	private array $orderBy = [];
	private bool $calcFoundRows = false;

	/**
	 * @param MySQL $db
	 * @param array<string, mixed> $options
	 */
	public function __construct($db, array $options = []) {
		parent::__construct($db, $options);
		$this->native = $this->createNativeBuilder('Select');
	}

	public function distinct(bool $distinct = true) {
		$this->native->setDistinct($distinct);

		return $this;
	}

	public function forUpdate(bool $enabled = true) {
		$this->native->setForUpdate($enabled);

		return $this;
	}

	public function field($expression, $alias = null) {
		if(is_object($expression)) {
			$expression = $this->formatFieldSubquery((string) $expression);
		}
		if($alias === null) {
			$this->fields[] = $expression;
		} else {
			$this->fields[$alias] = $expression;
		}
		$this->native->addField((string) $expression, $alias === null ? null : (string) $alias);

		return $this;
	}

	public function fields(array $fields) {
		foreach($fields as $alias => $expression) {
			$this->field($expression, is_int($alias) ? null : $alias);
		}

		return $this;
	}

	public function getFields(): array {
		return $this->fields;
	}

	public function from($alias, $table = null) {
		if($table === null) {
			[$alias, $table] = [null, $alias];
			if(!is_object($table) && !is_array($table)) {
				$table = (string) $table;
			}
		}
		$this->native->addTable($this->buildNativeTableName($alias, $table));

		return $this;
	}

	public function joinInner(string $alias, $table, ?string $expression = null, ...$args) {
		return $this->addNativeJoin('INNER', $alias, $table, $expression, $args);
	}

	public function joinLeft(string $alias, $table, string $expression, ...$args) {
		return $this->addNativeJoin('LEFT', $alias, $table, $expression, $args);
	}

	public function joinRight(string $alias, $table, string $expression, ...$args) {
		return $this->addNativeJoin('RIGHT', $alias, $table, $expression, $args);
	}

	/**
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	private function addNativeJoin(string $type, string $alias, $table, ?string $expression, array $args) {
		$condition = $expression === null ? null : $this->db()->quoteExpression($expression, $args);
		$this->native->addJoin($type, $this->buildNativeTableName($alias, $table), $condition);

		return $this;
	}

	public function where($expression, ...$args) {
		foreach($this->buildNativeConditionLines($expression, $args) as $condition) {
			$this->native->addWhere($condition);
		}

		return $this;
	}

	public function having($expression, ...$args) {
		foreach($this->buildNativeConditionLines($expression, $args) as $condition) {
			$this->native->addHaving($condition);
		}

		return $this;
	}

	public function groupBy(...$args) {
		foreach($args as $expression) {
			$expression = $this->normalizeNativeExpression($expression);
			if($expression !== null) {
				$this->native->addGroupBy($expression);
			}
		}

		return $this;
	}

	public function orderBy($expression, string $direction = 'ASC') {
		if($expression instanceof OrderBySpecification) {
			foreach($expression->getFields() as $field) {
				$this->addNativeOrder($field[0], $field[1]);
			}

			return $this;
		}
		$this->addNativeOrder($expression, $direction);

		return $this;
	}

	public function orderByValues(string $fieldName, array $values) {
		$expr = [];
		foreach(array_values($values) as $idx => $value) {
			$expr[] = $this->db()->quoteExpression('WHEN ? THEN ?', [$value, $idx]);
		}
		$this->addNativeOrder(sprintf("CASE %s\n\t\t%s\n\tEND", $this->db()->quoteField($fieldName), implode("\n\t\t", $expr)), 'ASC');

		return $this;
	}

	/**
	 * @param string|array<int, mixed> $expression
	 */
	private function addNativeOrder($expression, string $direction): void {
		$expression = $this->normalizeNativeExpression($expression);
		if($expression === null) {
			return;
		}
		$direction = $this->normalizeNativeDirection($direction);
		$this->orderBy[] = [$expression, $direction];
		$this->native->addOrderBy($expression, $direction);
	}

	public function getOrderBy() {
		return $this->orderBy;
	}

	public function resetOrderBy() {
		$this->orderBy = [];
		$this->native->clearOrderBy();

		return $this;
	}

	public function limit($limit) {
		$this->native->setLimit($this->normalizeNativeLimitOffset($limit));

		return $this;
	}

	public function offset($offset = 0) {
		$this->native->setOffset($this->normalizeNativeLimitOffset($offset));

		return $this;
	}

	public function union(...$queries) {
		foreach($queries as $query) {
			$this->native->addUnion((string) $query, false);
		}

		return $this;
	}

	public function unionAll(...$queries) {
		foreach($queries as $query) {
			$this->native->addUnion((string) $query, true);
		}

		return $this;
	}

	public function getCalcFoundRows(): bool {
		return $this->calcFoundRows;
	}

	public function setCalcFoundRows($calcFoundRows = true) {
		if(ini_get('mysql.trace_mode')) {
			throw new \RuntimeException('This function cant operate with mysql.trace_mode is set.');
		}
		$this->calcFoundRows = $calcFoundRows;
		$this->native->setCalcFoundRows((bool) $calcFoundRows);

		return $this;
	}

	public function temporary(array $schema, array $options = []): RunnableTemporaryTable {
		return parent::temporary($schema, $options);
	}

	public function __toString(): string {
		return $this->native->toString();
	}
}
