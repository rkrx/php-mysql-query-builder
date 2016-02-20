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
		return $this->createTempStatement(function (QueryStatement $statement) use ($callback) {
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
			return $data;
		});
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
		return $this->createTempStatement(function (QueryStatement $statement) {
			$row = $statement->fetch(\PDO::FETCH_ASSOC);
			if(!is_array($row)) {
				return array();
			}
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				$row = FieldValueConverter::convertValues($row, $columnDefinitions);
			}
			return $row;
		});
	}

	/**
	 * @param bool $treatValueAsArray
	 * @return mixed[]
	 */
	public function fetchKeyValue($treatValueAsArray = false) {
		return $this->createTempStatement(function (QueryStatement $statement) use ($treatValueAsArray) {
			if($treatValueAsArray) {
				$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
				$result = array();
				foreach($rows as $row) {
					list($key) = array_values($row);
					$result[$key] = $row;
				}
				return $result;
			}
			return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
		});
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
		return $this->createTempStatement(function (QueryStatement $stmt) {
			return $stmt->fetchAll(\PDO::FETCH_COLUMN);
		});
	}

	/**
	 * @param mixed $default
	 * @return null|bool|string|int|float
	 */
	public function fetchValue($default = null) {
		return $this->createTempStatement(function (QueryStatement $stmt) use ($default) {
			$result = $stmt->fetch(\PDO::FETCH_NUM);
			if($result !== false) {
				return $result[0];
			}
			return $default;
		});
	}

	/**
	 * @param bool $ignoreLimit
	 * @return int
	 * @throws \Exception
	 */
	public function fetchCount($ignoreLimit = true) {
		$tempLimit = $this->getLimit();
		$tempOffset = $this->getOffset();
		try {
			if($ignoreLimit) {
				$this->limit(null);
				$this->offset(null);
			}
			$result = $this->db()->select()
			->field('COUNT(*)')
			->from('COUNT_ALL_WRAPPER', $this)
			->debug()
			->fetchValue();
			$this->limit($tempLimit);
			$this->offset($tempOffset);
			return $result;
		} catch(\Exception $e) {
			$this->limit($tempLimit);
			$this->offset($tempOffset);
			throw $e;
		}
	}

	/**
	 * @return bool
	 */
	public function getFoundRows() {
		return $this->foundRows;
	}

	/**
	 * @param callback $fn
	 * @return mixed
	 * @throws \Exception
	 */
	private function createTempStatement($fn) {
		$stmt = $this->createStatement();
		$res = null;
		try {
			$res = call_user_func($fn, $stmt);
		} catch (\Exception $e) { // PHP 5.4 compatibility
			$stmt->closeCursor();
			throw $e;
		}
		$stmt->closeCursor();
		return $res;
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
