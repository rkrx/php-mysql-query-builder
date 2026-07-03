<?php

namespace Kir\MySQL\Rust;

use Kir\MySQL\Builder\Expr\OrderBySpecification;
use Kir\MySQL\Builder\RunnableUpdate;
use Kir\MySQL\Databases\MySQL;
use RuntimeException;

class RustRunnableUpdate extends RunnableUpdate {
	use NativeStatementSupport;

	/** @var object */
	private $native;
	/** @var array<int, array{alias: string|null, name: mixed}> */
	private array $tables = [];

	/**
	 * @param MySQL $db
	 * @param array<string, mixed> $options
	 */
	public function __construct($db, array $options = []) {
		parent::__construct($db, $options);
		$this->native = $this->createNativeBuilder('Update');
	}

	public function table($alias, $table = null): self {
		if($table === null) {
			[$alias, $table] = [null, $alias];
		}
		$this->tables[] = ['alias' => $alias, 'name' => $table];
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

	public function set(string $fieldName, $value): self {
		$this->native->addAssignment($fieldName, $this->db()->quote($value));

		return $this;
	}

	public function setDefault(string $fieldName): self {
		$this->native->addAssignment($fieldName, 'DEFAULT');

		return $this;
	}

	public function setExpr(string $expr, ...$args): self {
		$this->native->addRawAssignment(count($args) > 0 ? $this->db()->quoteExpression($expr, $args) : $expr);

		return $this;
	}

	public function setAll(array $data, ?array $allowedFields = null): self {
		if($allowedFields !== null) {
			foreach($data as $fieldName => $value) {
				if(in_array($fieldName, $allowedFields, true)) {
					$this->set($fieldName, $value);
				}
			}

			return $this;
		}
		foreach($this->filterNativeUpdateValues($data) as $fieldName => $value) {
			$this->set($fieldName, $value);
		}

		return $this;
	}

	public function setMask(array $mask) {
		parent::setMask($mask);
		$this->native->setMask(array_values($mask));

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

	/**
	 * @param array<string, mixed> $values
	 * @return array<string, mixed>
	 */
	private function filterNativeUpdateValues(array $values): array {
		if(!count($values)) {
			return [];
		}
		if(!count($this->tables)) {
			throw new RuntimeException('Table name is missing');
		}
		if(count($this->tables) > 1) {
			throw new RuntimeException('Batch values only work with max. one table');
		}

		$tableName = $this->tables[0]['name'];
		$result = [];
		if(is_string($tableName)) {
			$fields = $this->db()->getTableFields($tableName);
			foreach($values as $fieldName => $fieldValue) {
				if(in_array($fieldName, $fields, true)) {
					$result[$fieldName] = $fieldValue;
				}
			}
		}

		return $result;
	}

	public function __toString(): string {
		return $this->native->toString();
	}
}
