<?php

namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DefaultValue;
use Kir\MySQL\Builder\Internal\Types;
use Kir\MySQL\Builder\Traits\JoinBuilder;
use Kir\MySQL\Builder\Traits\LimitBuilder;
use Kir\MySQL\Builder\Traits\OffsetBuilder;
use Kir\MySQL\Builder\Traits\OrderByBuilder;
use Kir\MySQL\Builder\Traits\TableBuilder;
use Kir\MySQL\Builder\Traits\TableNameBuilder;
use Kir\MySQL\Builder\Traits\WhereBuilder;
use RuntimeException;

/**
 * @phpstan-import-type DBTableNameType from Types
 */
abstract class Update extends InsertUpdateStatement {
	use TableNameBuilder;
	use TableBuilder;
	use JoinBuilder;
	use WhereBuilder;
	use OrderByBuilder;
	use LimitBuilder;
	use OffsetBuilder;

	/** @var mixed[] */
	private array $fields = [];

	/**
	 * @param ($table is null ? DBTableNameType : string) $alias
	 * @param null|DBTableNameType $table
	 * @return $this
	 */
	public function table($alias, $table = null): self {
		if($table === null) {
			[$alias, $table] = [$table, $alias];
		}
		$this->addTable($alias, $table);

		return $this;
	}

	/**
	 * @param string $fieldName
	 * @param mixed $value
	 * @return $this
	 */
	public function set(string $fieldName, $value): self {
		$sqlField = $fieldName;
		$sqlValue = $this->db()->quote($value);
		$this->fields[$sqlField] = $sqlValue;

		return $this;
	}

	/**
	 * @param string $fieldName
	 * @return $this
	 */
	public function setDefault(string $fieldName): self {
		$sqlField = $fieldName;
		$this->fields[$sqlField] = new DefaultValue();

		return $this;
	}

	/**
	 * @param string $expr
	 * @param mixed ...$args
	 * @return $this
	 */
	public function setExpr(string $expr, ...$args): self {
		if(count($args) > 0) {
			$this->fields[] = func_get_args();
		} else {
			$this->fields[] = $expr;
		}

		return $this;
	}

	/**
	 * @param array<string, mixed> $data
	 * @param array<int, string>|null $allowedFields
	 * @return $this
	 */
	public function setAll(array $data, ?array $allowedFields = null): self {
		if($allowedFields !== null) {
			foreach($data as $fieldName => $value) {
				if(in_array($fieldName, $allowedFields)) {
					$this->set($fieldName, $value);
				}
			}
		} else {
			$values = $this->clearValues($data);
			foreach($values as $fieldName => $value) {
				$this->set($fieldName, $value);
			}
		}

		return $this;
	}

	/**
	 * @param array<string, mixed> $params
	 * @return int
	 */
	abstract public function run(array $params = []): int;

	/**
	 * @return string
	 */
	public function __toString(): string {
		$query = "UPDATE\n";
		$query = $this->buildTables($query);
		$query = $this->buildJoins($query);
		$query .= "SET\n";
		$query = $this->buildAssignments($query);
		$query = $this->buildWhereConditions($query);
		$query = $this->buildOrder($query);
		$query = $this->buildLimit($query);
		$query = $this->buildOffset($query);

		return $query;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function buildAssignments(string $query): string {
		$sqlFields = $this->buildFieldList($this->fields);
		if(!count($sqlFields)) {
			throw new RuntimeException('No field-data found');
		}

		return sprintf("%s%s\n", $query, implode(",\n", $sqlFields));
	}

	/**
	 * @param array<string, mixed> $values
	 * @return array<string, mixed>
	 */
	private function clearValues(array $values): array {
		if(!count($values)) {
			return [];
		}
		$tables = $this->getTables();
		if(!count($tables)) {
			throw new RuntimeException('Table name is missing');
		}
		if(count($tables) > 1) {
			throw new RuntimeException('Batch values only work with max. one table');
		}

		$table = $tables[0];
		$tableName = $table['name'];

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
}
