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

class Update extends InsertUpdateStatement {
	use TableNameBuilder;
	use TableBuilder;
	use JoinBuilder;
	use WhereBuilder;
	use OrderByBuilder;
	use LimitBuilder;
	use OffsetBuilder;

	/**
	 * @var array
	 */
	private $fields = array();

	/**
	 * @param string $alias
	 * @param string $table
	 * @return $this
	 */
	public function table($alias, $table = null) {
		if($table === null) {
			list($alias, $table) = [$table, $alias];
		}
		$this->addTable($alias, $table);
		return $this;
	}

	/**
	 * @param string $fieldName
	 * @param string $value
	 * @throws \UnexpectedValueException
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
	 * @return $this
	 */
	public function setExpr($expr) {
		$this->fields[] = $expr;
		return $this;
	}

	/**
	 * @param array $data
	 * @param array $allowedFields
	 * @return $this
	 * @throws Exception
	 */
	public function setAll(array $data, array $allowedFields = null) {
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
	 * @throws Exception
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
		$query .= ";\n";
		return $query;
	}

	/**
	 * @param string $query
	 * @return string
	 * @throws Exception
	 */
	private function buildAssignments($query) {
		$sqlFields = $this->buildFieldList($this->fields);
		if (!count($sqlFields)) {
			throw new Exception('No field-data found');
		}
		return sprintf("%s%s\n", $query, join(",\n", $sqlFields));
	}

	/**
	 * @param array $values
	 * @return array
	 * @throws Exception
	 */
	private function clearValues(array $values) {
		if(!count($values)) {
			return [];
		}
		$tables = $this->getTables();
		if(!count($tables)) {
			throw new Exception('Table name is missing');
		}
		if(count($tables) > 1) {
			throw new Exception('Batch values only work with max. one table');
		}
		list($table) = $tables;
		$tableName = $table['name'];

		$fields = $this->db()->getTableFields($tableName);
		$result = array();

		foreach ($values as $fieldName => $fieldValue) {
			if(in_array($fieldName, $fields)) {
				$result[$fieldName] = $fieldValue;
			}
		}

		return $result;
	}
}