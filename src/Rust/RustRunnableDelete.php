<?php

namespace Kir\MySQL\Rust;

use Kir\MySQL\Builder\Expr\OrderBySpecification;
use Kir\MySQL\Builder\RunnableDelete;
use Kir\MySQL\Databases\MySQL;

class RustRunnableDelete extends RunnableDelete {
	use NativeStatementSupport;

	/** @var object */
	private $native;

	/**
	 * @param MySQL $db
	 * @param array<string, mixed> $options
	 */
	public function __construct($db, array $options = []) {
		parent::__construct($db, $options);
		$this->native = $this->createNativeBuilder('Delete');
	}

	public function from($alias, $table = null) {
		$deleteAlias = null;
		if($table !== null) {
			$deleteAlias = (string) $alias;
		}
		if($table === null) {
			[$alias, $table] = [null, $alias];
		}
		$this->native->addTable($this->buildNativeTableName($alias, $table), $deleteAlias);

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
		$this->native->addOrderBy($expression, $this->normalizeNativeDirection($direction));
	}

	public function limit($limit) {
		$this->native->setLimit($this->normalizeNativeLimitOffset($limit));

		return $this;
	}

	public function offset($offset = 0) {
		$this->native->setOffset($this->normalizeNativeLimitOffset($offset));

		return $this;
	}

	public function __toString(): string {
		return $this->native->toString();
	}
}
