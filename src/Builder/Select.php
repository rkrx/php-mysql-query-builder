<?php
namespace Kir\MySQL\Builder;

use Generator;
use IteratorAggregate;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;
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
use Kir\MySQL\Tools\VirtualTable;
use RuntimeException;

/**
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
abstract class Select extends Statement implements IteratorAggregate {
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

	/** @var array<int|string, string> */
	private $fields = [];
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
	public function distinct(bool $distinct = true): self {
		$this->distinct = $distinct;
		return $this;
	}

	/**
	 * @param string|Select $expression
	 * @param string|null $alias
	 * @return $this
	 */
	public function field($expression, $alias = null): self {
		if (is_object($expression)) {
			$expression = (string) $expression;
			$expression = trim($expression);
			$expression = rtrim($expression, ';');
			$expression = trim($expression);
			$lines = explode("\n", $expression);
			$lines = array_map(static function($line) { return "\t\t{$line}"; }, $lines);
			$expression = implode("\n", $lines);
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
	 * @param array<string, string>|array<int, string> $fields
	 * @return $this
	 */
	public function fields(array $fields): self {
		foreach ($fields as $alias => $expression) {
			$this->field($expression, is_int($alias) ? null : $alias);
		}
		return $this;
	}

	/**
	 * @return array<int|string, string>
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * @param bool $enabled
	 * @return $this
	 */
	public function forUpdate(bool $enabled = true) {
		$this->forUpdate = $enabled;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getCalcFoundRows(): bool {
		return $this->calcFoundRows;
	}

	/**
	 * @param bool $calcFoundRows
	 * @return $this
	 */
	public function setCalcFoundRows($calcFoundRows = true): self {
		if (ini_get("mysql.trace_mode")) {
			throw new RuntimeException('This function cant operate with mysql.trace_mode is set.');
		}
		$this->calcFoundRows = $calcFoundRows;
		return $this;
	}

	/**
	 * @param null|string $alias
	 * @param null|string|Select|VirtualTable|array<int, null|int|float|string|array<string, mixed>> $table
	 * @return $this
	 */
	public function from(?string $alias, $table = null): self {
		if($table === null) {
			[$alias, $table] = [$table, $alias];
			$this->addTable($alias, (string) $table);
		} else {
			$this->addTable($alias, $table);
		}
		return $this;
	}

	/**
	 * @param array<string, mixed> $values
	 * @return $this
	 */
	abstract public function bindValues(array $values);

	/**
	 * @param string $key
	 * @param string|int|bool|float|null $value
	 * @return $this
	 */
	abstract public function bindValue(string $key, $value);

	/**
	 * @return $this
	 */
	abstract public function clearValues();

	/**
	 * @param bool $preserveTypes
	 * @return $this
	 */
	abstract public function setPreserveTypes(bool $preserveTypes = true);

	/**
	 * @param null|callable(array<string, mixed>): array<string, mixed>|callable(array<string, mixed>): void|callable(array<string, mixed>): DBIgnoreRow $callback
	 * @return array<int, array<string, mixed>>
	 */
	abstract public function fetchRows($callback = null): array;

	/**
	 * @param null|callable(array<string, mixed>): (array<mixed, mixed>|null|void) $callback
	 * @return Generator<int, array<string, mixed>>
	 */
	abstract public function fetchRowsLazy($callback = null);

	/**
	 * @param null|callable(array<string, mixed>): array<string, mixed>|callable(array<string, mixed>): void|callable(array<string, mixed>): DBIgnoreRow $callback
	 * @return array<string, mixed>
	 */
	abstract public function fetchRow($callback = null): array;

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return T[]|U[]
	 */
	abstract public function fetchObjects(string $className = 'stdClass', $callback = null): array;

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return Generator<int, T|U>
	 */
	abstract public function fetchObjectsLazy($className = null, $callback = null);

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return T|U
	 */
	abstract public function fetchObject($className = null, $callback = null);

	/**
	 * @param bool $treatValueAsArray
	 * @return array<mixed, mixed>
	 */
	abstract public function fetchKeyValue($treatValueAsArray = false): array;

	/**
	 * @param string[] $fields
	 * @return array<string, array<int, mixed>>
	 */
	abstract public function fetchGroups(array $fields): array;

	/**
	 * @template T
	 * @param null|callable(null|bool|int|float|string): T $fn
	 * @return array<int, T|string>
	 */
	abstract public function fetchArray(?callable $fn = null): array;

	/**
	 * @template TDefault
	 * @template TCastFn
	 * @param TDefault $default
	 * @param null|callable(null|bool|string|int|float): TCastFn $fn
	 * @return null|bool|string|int|float|TDefault|TCastFn
	 */
	abstract public function fetchValue($default = null, ?callable $fn = null);

	/**
	 * @return int
	 */
	abstract public function getFoundRows();

	/**
	 * @return string
	 */
	public function __toString(): string {
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
		$query = $this->buildLimit($query, $this->getOffset());
		$query = $this->buildOffset($query);
		$query = $this->buildUnions($query);
		$query = $this->buildForUpdate($query);
		return $query;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildFields(string $query): string {
		$fields = [];
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
		return $query.implode(",\n", $fields)."\n";
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildForUpdate(string $query): string {
		if ($this->forUpdate) {
			$query .= "FOR UPDATE\n";
		}
		return $query;
	}
}
