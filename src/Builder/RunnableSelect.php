<?php
namespace Kir\MySQL\Builder;

use Closure;
use Generator;
use IteratorAggregate;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;
use Kir\MySQL\Builder\Helpers\FieldTypeProvider;
use Kir\MySQL\Builder\Helpers\FieldValueConverter;
use Kir\MySQL\Builder\Helpers\LazyRowGenerator;
use Kir\MySQL\Builder\Helpers\YieldPolyfillIterator;
use PDO;
use Traversable;

/**
 */
class RunnableSelect extends Select implements IteratorAggregate {
	/** @var array */
	private $values = array();
	/** @var bool */
	private $preserveTypes = false;
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
	 * @param Closure $callback
	 * @return array[]
	 */
	public function fetchRows(Closure $callback = null) {
		return $this->fetchAll($callback, PDO::FETCH_ASSOC);
	}

	/**
	 * @param Closure $callback
	 * @return array[]|\Generator
	 */
	public function fetchRowsLazy(Closure $callback = null) {
		return $this->fetchLazy($callback, PDO::FETCH_ASSOC);
	}

	/**
	 * @param Closure|null $callback
	 * @return mixed[]
	 * @throws \Exception
	 */
	public function fetchRow(Closure $callback = null) {
		return $this->fetch($callback, PDO::FETCH_ASSOC, null, function ($row) {
			return ['valid' => is_array($row), 'default' => []];
		});
	}

	/**
	 * @param string $className
	 * @param Closure $callback
	 * @return object[]
	 * @throws \Exception
	 */
	public function fetchObjects($className, Closure $callback = null) {
		return $this->fetchAll($callback, PDO::FETCH_CLASS, $className);
	}

	/**
	 * @param string $className
	 * @param Closure $callback
	 * @return object[]|Generator
	 */
	public function fetchObjectsLazy($className, Closure $callback = null) {
		return $this->fetchLazy($callback, PDO::FETCH_CLASS, $className);
	}

	/**
	 * @param string $className
	 * @param Closure|null $callback
	 * @return object[]
	 * @throws \Exception
	 */
	public function fetchObject($className, Closure $callback = null) {
		return $this->fetch($callback, PDO::FETCH_CLASS, $className, function ($row) {
			return ['valid' => is_object($row), 'default' => null];
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

	/**
	 * @return Traversable|array[]|\Generator
	 */
	public function getIterator() {
		return $this->fetchRowsLazy();
	}

	/**
	 * @param Closure $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return mixed
	 * @throws \Exception
	 */
	private function fetchAll(Closure $callback = null, $mode, $arg0 = null) {
		return $this->createTempStatement(function (QueryStatement $statement) use ($callback, $mode, $arg0) {
			$statement->setFetchMode($mode, $arg0);
			$data = $statement->fetchAll();
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				foreach($data as &$row) {
					$row = FieldValueConverter::convertValues($row, $columnDefinitions);
				}
			}
			if($callback !== null) {
				return call_user_func(function ($resultData = []) use ($data, $callback) {
					foreach($data as $row) {
						$result = $callback($row);
						if($result !== null && !($result instanceof DBIgnoreRow)) {
							$resultData[] = $result;
						} else {
							$resultData[] = $row;
						}
					}
					return $resultData;
				});
			}
			return $data;
		});
	}

	/**
	 * @param Closure $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return Generator|YieldPolyfillIterator|mixed[]
	 */
	private function fetchLazy(Closure $callback = null, $mode, $arg0 = null) {
		if(version_compare(PHP_VERSION, '5.5', '<')) {
			return new YieldPolyfillIterator($callback, $this->preserveTypes, function () use ($mode, $arg0) {
				$statement = $this->createStatement();
				$statement->setFetchMode($mode, $arg0);
				return $statement;
			});
		}
		$statement = $this->createStatement();
		$statement->setFetchMode($mode, $arg0);
		$generator = new LazyRowGenerator($this->preserveTypes);
		return $generator->generate($statement, $callback);
	}

	/**
	 * @param Closure $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @param Closure $resultValidator
	 * @return mixed
	 * @throws \Exception
	 */
	private function fetch(Closure $callback = null, $mode, $arg0 = null, Closure $resultValidator = null) {
		return $this->createTempStatement(function (QueryStatement $statement) use ($callback, $mode, $arg0, $resultValidator) {
			$statement->setFetchMode($mode, $arg0);
			$row = $statement->fetch();
			$result = $resultValidator($row);
			if(!$result['valid']) {
				return $result['default'];
			}
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				$row = FieldValueConverter::convertValues($row, $columnDefinitions);
			}
			if($callback !== null) {
				$result = $callback($row);
				if($result !== null) {
					$row = $result;
				}
			}
			return $row;
		});
	}
}
