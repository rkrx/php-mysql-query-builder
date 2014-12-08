<?php
namespace Kir\MySQL\Builder;

/**
 */
class RunnableSelect extends Select {
	/**
	 * @var array
	 */
	private $values = array();

	/**
	 * @var bool
	 */
	private $preserveTypes;

	/**
	 * @var bool
	 */
	private $foundRows = 0;

	/**
	 * @param array $values
	 * @return $this
	 */
	public function bindValues(array $values) {
		$this->values = array_merge($this->values, $values);
		return $this;
	}

	/**
	 * @param string $key
	 * @param string|int|bool|float|null $value
	 * @return $this
	 */
	public function bindValue($key, $value) {
		$this->values[$key] = $value;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function clearValues() {
		$this->values = array();
		return $this;
	}

	/**
	 * @param bool $preserveTypes
	 * @return $this
	 */
	public function setPreserveTypes($preserveTypes = true) {
		$this->preserveTypes = $preserveTypes;
		return $this;
	}

	/**
	 * @param \Closure $callback
	 * @return array[]
	 */
	public function fetchRows(\Closure $callback = null) {
		$statement = $this->createStatement();
		$data = $statement->fetchAll(\PDO::FETCH_ASSOC);
		if($callback !== null) {
			$data = array_map($callback, $data);
		}
		if($this->preserveTypes) {
			$columnDefinitions = $this->getFieldTypes($statement);
			foreach($data as &$row) {
				$row = $this->convertValues($row, $columnDefinitions);
			}
		}
		$statement->closeCursor();
		return $data;
	}

	/**
	 * @return string[]
	 */
	public function fetchRow() {
		$statement = $this->createStatement();
		$row = $statement->fetch(\PDO::FETCH_ASSOC);
		if(!is_array($row)) {
			return array();
		}
		if($this->preserveTypes) {
			$columnDefinitions = $this->getFieldTypes($statement);
			$row = $this->convertValues($row, $columnDefinitions);
		}
		$statement->closeCursor();
		return $row;
	}

	/**
	 * @return mixed[]
	 */
	public function fetchKeyValue() {
		$rows = $this->fetchRows();
		$result = array();
		foreach($rows as $row) {
			list($key, $value) = array_values($row);
			$result[$key] = $value;
		}
		return $result;
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public function fetchGroups(array $fields) {
		$rows = $this->fetchRows();
		$result = array();
		foreach($rows as $row) {
			$tmp = &$result;
			foreach($fields as $field) {
				$value = $row[$field];
				if(!array_key_exists($value, $tmp)) {
					$tmp[$value] = [];
				}
				$tmp = &$tmp[$value];
			}
			$tmp[] = $row;
		}
		return $result;
	}

	/**
	 * @return string[]
	 */
	public function fetchArray() {
		return $this->fetchRows(function ($row) {
			reset($row);
			return current($row);
		});
	}

	/**
	 * @param mixed $default
	 * @return null|bool|string|int|float
	 */
	public function fetchValue($default = null) {
		$statement = $this->createStatement();
		$row = $statement->fetch(\PDO::FETCH_ASSOC);
		if(!is_array($row)) {
			return $default;
		}
		if(!count($row)) {
			return null;
		}
		$statement->closeCursor();
		return array_shift($row);
	}

	/**
	 * @return bool
	 */
	public function getFoundRows() {
		return $this->foundRows;
	}

	/**
	 * @return \PDOStatement
	 */
	private function createStatement() {
		$db = $this->db();
		$query = $this->__toString();
		$statement = $db->query($query);
		$statement->execute($this->values);
		if($this->getCalcFoundRows()) {
			$this->foundRows = $db->query('SELECT FOUND_ROWS()')->fetchColumn();
		}
		return $statement;
	}

	/**
	 * @param \PDOStatement $statement
	 * @return array
	 */
	private function getFieldTypes($statement) {
		$c = $statement->columnCount();
		$fieldTypes = array();
		for($i=0; $i<$c+20; $i++) {
			$column = $statement->getColumnMeta($i);
			$fieldTypes[$column['name']] = $this->getTypeFromNativeType($column['native_type']);
		}
		return $fieldTypes;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	private function getTypeFromNativeType($type) {
		switch ($type) {
			case 'NEWDECIMAL':
			case 'DECIMAL':
			case 'FLOAT':
			case 'DOUBLE':
				return 'f';
			case 'TINY':
			case 'SHORT':
			case 'LONG':
			case 'LONGLONG':
			case 'INT24':
				return 'i';
		}
		return $type;
	}

	/**
	 * @param array $row
	 * @param array $columnDefinitions
	 * @return mixed
	 */
	private function convertValues(array $row, array $columnDefinitions) {
		foreach($row as $key => &$value) {
			if($value !== null) {
				$value = $this->convertValue($value, $columnDefinitions[$key]);
			}
		}
		return $row;
	}

	/**
	 * @param mixed $value
	 * @param string $type
	 * @return mixed
	 */
	private function convertValue($value, $type) {
		switch ($type) {
			case 'i':
				return (int) $value;
			case 'f':
				return (float) $value;
		}
		return $value;
	}
}