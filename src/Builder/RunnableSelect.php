<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Helpers\FieldTypeProvider;
use Kir\MySQL\Builder\Helpers\FieldValueConverter;
use Kir\MySQL\Builder\Helpers\LazyRowGenerator;

/**
 */
class RunnableSelect extends Select {
	/** @var array */
	private $values = array();
	/** @var bool */
	private $preserveTypes;
	/** @var int */
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
		if($this->preserveTypes) {
			$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
			foreach($data as &$row) {
				$row = FieldValueConverter::convertValues($row, $columnDefinitions);
			}
		}
		if($callback !== null) {
			$data = array_map($callback, $data);
		}
		$statement->closeCursor();
		return $data;
	}

	/**
	 * @param \Closure $callback
	 * @return array[]|\Generator
	 */
	public function fetchRowsLazy(\Closure $callback = null) {
		if(version_compare(PHP_VERSION, '5.5', '<=')) {
			return $this->fetchRows($callback);
		}
		$statement = $this->createStatement();
		$generator = new LazyRowGenerator($this->preserveTypes);
		return $generator->generate($statement, $callback);
	}

	/**
	 * @return string[]
	 */
	public function fetchRow() {
		$statement = $this->createStatement();
		$row = $statement->fetch(\PDO::FETCH_ASSOC);
		if(!is_array($row)) {
			$statement->closeCursor();
			return array();
		}
		if($this->preserveTypes) {
			$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
			$row = FieldValueConverter::convertValues($row, $columnDefinitions);
		}
		$statement->closeCursor();
		return $row;
	}

	/**
	 * @param bool $treatValueAsArray
	 * @return mixed[]
	 */
	public function fetchKeyValue($treatValueAsArray = false) {
		$rows = $this->fetchRows();
		$result = array();
		if(!$treatValueAsArray) {
			foreach($rows as $row) {
				list($key, $value) = array_values($row);
				$result[$key] = $value;
			}
		} else {
			foreach($rows as $row) {
				list($key) = array_values($row);
				$result[$key] = $row;
			}
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
		$statement->closeCursor();
		if(!is_array($row)) {
			return $default;
		}
		if(!count($row)) {
			return null;
		}
		return array_shift($row);
	}

	/**
	 * @return bool
	 */
	public function getFoundRows() {
		return $this->foundRows;
	}

	/**
	 * @return QueryStatement
	 */
	private function createStatement() {
		$db = $this->db();
		$query = $this->__toString();
		$statement = $db->prepare($query);
		$statement->execute($this->values);
		if($this->getCalcFoundRows()) {
			$this->foundRows = (int) $db->query('SELECT FOUND_ROWS()')->fetchColumn();
		}
		return $statement;
	}
}
