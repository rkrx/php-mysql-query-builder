<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DefaultValue;
use Kir\MySQL\Builder\Traits\JoinBuilder;
use Kir\MySQL\Builder\Traits\LimitBuilder;
use Kir\MySQL\Builder\Traits\OffsetBuilder;
use Kir\MySQL\Builder\Traits\OrderByBuilder;
use Kir\MySQL\Builder\Traits\TableBuilder;
use Kir\MySQL\Builder\Traits\TableNameBuilder;
use Kir\MySQL\Builder\Traits\WhereBuilder;
use RuntimeException;

class Update extends InsertUpdateStatement {
	use TableNameBuilder;
	use TableBuilder;
	use JoinBuilder;
	use WhereBuilder;
	use OrderByBuilder;
	use LimitBuilder;
	use OffsetBuilder;

	/** @var mixed[] */
	private $fields = [];

	/**
	 * @param string $alias
	 * @param string $table
	 * @return $this
	 */
	public function table($alias, $table = null) {
		$this->addTable($alias, $table);
		return $this;
	}

	/**
	 * @param string $fieldName
	 * @param string $value
	 * @return $this
	 */
	public function set($fieldName, $value) {
		$sqlField = $fieldName;
		$sqlValue = $this->db()->quote($value);
		$this->fields[$sqlField] = $sqlValue;
		return $this;
	}

	/**
	 * @param string $fieldName
	 * @return $this
	 */
	public function setDefault($fieldName) {
		$sqlField = $fieldName;
		$this->fields[$sqlField] = new DefaultValue();
		return $this;
	}

	/**
	 * @param string $expr
	 * @param mixed ...$args
	 * @return $this
	 */
	public function setExpr($expr, ...$args) {
		if(count($args) > 0) {
			$this->fields[] = func_get_args();
		} else {
			$this->fields[] = $expr;
		}
		return $this;
	}

	/**
	 * @param array $data
	 * @param array $allowedFields
	 * @return $this
	 */
	public function setAll(array $data, array $allowedFields = null) {
		if ($allowedFields !== null) {
			foreach ($data as $fieldName => $value) {
				if (in_array($fieldName, $allowedFields)) {
					$this->set($fieldName, $value);
				}
			}
		} else {
			$values = $this->clearValues($data);
			foreach ($values as $fieldName => $value) {
				$this->set($fieldName, $value);
			}
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString() {
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
	private function buildAssignments($query) {
		$sqlFields = $this->buildFieldList($this->fields);
		if (!count($sqlFields)) {
			throw new RuntimeException('No field-data found');
		}
		return sprintf("%s%s\n", $query, implode(",\n", $sqlFields));
	}

	/**
	 * @param array $values
	 * @return array
	 */
	private function clearValues(array $values) {
		if (!count($values)) {
			return [];
		}
		$tables = $this->getTables();
		if (!count($tables)) {
			throw new RuntimeException('Table name is missing');
		}
		if (count($tables) > 1) {
			throw new RuntimeException('Batch values only work with max. one table');
		}
		list($table) = $tables;
		$tableName = $table['name'];

		$fields = $this->db()->getTableFields($tableName);
		$result = [];

		foreach ($values as $fieldName => $fieldValue) {
			if (in_array($fieldName, $fields)) {
				$result[$fieldName] = $fieldValue;
			}
		}

		return $result;
	}
}
