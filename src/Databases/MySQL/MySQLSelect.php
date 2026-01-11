<?php

namespace Kir\MySQL\Databases\MySQL;

use Kir\MySQL\Builder\Internal\Types;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Builder\RunnableTemporaryTable;
use Kir\MySQL\Builder\Select;
use Kir\MySQL\Builder\Statement;
use Kir\MySQL\Builder\Traits\GroupByBuilder;
use Kir\MySQL\Builder\Traits\HavingBuilder;
use Kir\MySQL\Builder\Traits\JoinBuilder;
use Kir\MySQL\Builder\Traits\LimitBuilder;
use Kir\MySQL\Builder\Traits\OffsetBuilder;
use Kir\MySQL\Builder\Traits\OrderByBuilder;
use Kir\MySQL\Builder\Traits\TableBuilder;
use Kir\MySQL\Builder\Traits\TableNameBuilder;
use Kir\MySQL\Builder\Traits\UnionBuilder;
use Kir\MySQL\Builder\Traits\WhereBuilder;
use RuntimeException;

/**
 * @phpstan-import-type DBTableNameType from Types
 */
abstract class MySQLSelect extends Statement implements RunnableSelect {
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
	private array $fields = [];
	private bool $calcFoundRows = false;
	private bool $forUpdate = false;
	private bool $distinct = false;

	/**
	 * @param bool $distinct
	 * @return $this
	 */
	public function distinct(bool $distinct = true) {
		$this->distinct = $distinct;

		return $this;
	}

	/**
	 * @param array<int|string, string> $schema
	 * @param array<string, mixed> $options
	 * @return RunnableTemporaryTable
	 */
	public function temporary(array $schema, array $options = []): RunnableTemporaryTable {
		$name = 'tmp_' . bin2hex(random_bytes(16));

		$schemaSql = '';
		if($schema) {
			$lines = [];
			foreach($schema as $key => $val) {
				if(is_int($key)) {
					$lines[] = $val;
				} else {
					$lines[] = "`{$key}` {$val}";
				}
			}
			$schemaSql = '(' . implode(', ', $lines) . ')';
		}

		$optionsSql = '';
		if(isset($options['engine'])) {
			$optionsSql .= " ENGINE={$options['engine']}";
		}

		$query = (string) $this;
		$sql = "CREATE TEMPORARY TABLE {$name} {$schemaSql} {$optionsSql} AS {$query}";

		$this->db()->exec($sql);

		return new MySQLTemporaryTable($this->db(), $name);
	}

	/**
	 * @param string|Select $expression
	 * @param string|null $alias
	 * @return $this
	 */
	public function field($expression, $alias = null) {
		if(is_object($expression)) {
			$expression = (string) $expression;
			$expression = trim($expression);
			$expression = rtrim($expression, ';');
			$expression = trim($expression);
			$lines = explode("\n", $expression);
			$lines = array_map(static fn($line) => "\t\t{$line}", $lines);
			$expression = implode("\n", $lines);
			$expression = sprintf("(\n%s\n\t)", $expression);
		}
		if($alias === null) {
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
	public function fields(array $fields) {
		foreach($fields as $alias => $expression) {
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
	public function setCalcFoundRows($calcFoundRows = true) {
		if(ini_get("mysql.trace_mode")) {
			throw new RuntimeException('This function cant operate with mysql.trace_mode is set.');
		}
		$this->calcFoundRows = $calcFoundRows;

		return $this;
	}

	/**
	 * @param ($table is null ? DBTableNameType : string) $alias
	 * @param null|DBTableNameType $table
	 * @return $this
	 */
	public function from($alias, $table = null) {
		if($table === null) {
			[$alias, $table] = [$table, $alias];
			$this->addTable($alias, (string) $table);
		} else {
			$this->addTable($alias, $table);
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		$query = "SELECT";
		if($this->calcFoundRows) {
			$query .= " SQL_CALC_FOUND_ROWS";
		}
		if($this->distinct) {
			$query .= " DISTINCT";
		}
		$query .= "\n";
		$query = $this->buildFields($query);
		if(count($this->getTables())) {
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

		return $query . implode(",\n", $fields) . "\n";
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildForUpdate(string $query): string {
		if($this->forUpdate) {
			$query .= "FOR UPDATE\n";
		}

		return $query;
	}
}
