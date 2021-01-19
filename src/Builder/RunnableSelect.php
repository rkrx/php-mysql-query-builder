<?php
namespace Kir\MySQL\Builder;

use Closure;
use Generator;
use IteratorAggregate;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;
use Kir\MySQL\Builder\Helpers\FieldTypeProvider;
use Kir\MySQL\Builder\Helpers\FieldValueConverter;
use Kir\MySQL\Builder\Helpers\LazyRowGenerator;
use Kir\MySQL\Databases\MySQL;
use PDO;
use stdClass;
use Traversable;

/**
 */
class RunnableSelect extends Select implements IteratorAggregate {
	/** @var array */
	private $values = [];
	/** @var bool */
	private $preserveTypes;
	/** @var string */
	private $defaultClassName;
	/** @var int */
	private $foundRows = 0;

	/**
	 * @param MySQL $db
	 * @param array $options
	 */
	public function __construct(MySQL $db, array $options = []) {
		parent::__construct($db);
		$this->preserveTypes = array_key_exists('preserve-types-default', $options) ? $options['preserve-types-default'] : false;
		$this->defaultClassName = array_key_exists('fetch-object-class-default', $options) ? $options['fetch-object-class-default'] : stdClass::class;
	}

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
	public function bindValue(string $key, $value) {
		$this->values[$key] = $value;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function clearValues() {
		$this->values = [];
		return $this;
	}

	/**
	 * @param bool $preserveTypes
	 * @return $this
	 */
	public function setPreserveTypes(bool $preserveTypes = true) {
		$this->preserveTypes = $preserveTypes;
		return $this;
	}

	/**
	 * @param Closure|null $callback
	 * @return array[]
	 */
	public function fetchRows(Closure $callback = null): array {
		return $this->fetchAll($callback, PDO::FETCH_ASSOC);
	}

	/**
	 * @param Closure|null $callback
	 * @return Traversable|mixed[]
	 */
	public function fetchRowsLazy(Closure $callback = null) {
		return $this->fetchLazy($callback, PDO::FETCH_ASSOC);
	}

	/**
	 * @param Closure|null $callback
	 * @return mixed[]
	 */
	public function fetchRow(Closure $callback = null): array {
		return $this->fetch($callback, PDO::FETCH_ASSOC, null, static function ($row) {
			return ['valid' => is_array($row), 'default' => []];
		});
	}

	/**
	 * @param string $className
	 * @param Closure|null $callback
	 * @return object[]
	 */
	public function fetchObjects($className = null, ?Closure $callback = null): array {
		return $this->fetchAll($callback, PDO::FETCH_CLASS, $className ?: $this->defaultClassName);
	}

	/**
	 * @param string $className
	 * @param Closure|null $callback
	 * @return Generator|object[]
	 */
	public function fetchObjectsLazy($className = null, ?Closure $callback = null) {
		yield from $this->fetchLazy($callback, PDO::FETCH_CLASS, $className ?: $this->defaultClassName);
	}

	/**
	 * @param string $className
	 * @param Closure|null $callback
	 * @return object[]
	 */
	public function fetchObject($className = null, Closure $callback = null): array {
		return $this->fetch($callback, PDO::FETCH_CLASS, $className ?: $this->defaultClassName, static function ($row) {
			return ['valid' => is_object($row), 'default' => null];
		});
	}

	/**
	 * @param bool $treatValueAsArray
	 * @return mixed[]
	 */
	public function fetchKeyValue($treatValueAsArray = false): array {
		return $this->createTempStatement(static function (QueryStatement $statement) use ($treatValueAsArray) {
			if($treatValueAsArray) {
				$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
				$result = [];
				foreach($rows as $row) {
					list($key) = array_values($row);
					$result[$key] = $row;
				}
				return $result;
			}
			return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
		});
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public function fetchGroups(array $fields): array {
		$rows = $this->fetchRows();
		$result = [];
		foreach($rows as $row) {
			/** @var array $tmp */
			$tmp = &$result;
			foreach($fields as $field) {
				$value = (string) $row[$field];
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
	public function fetchArray(): array {
		return $this->createTempStatement(static function (QueryStatement $stmt) {
			return $stmt->fetchAll(PDO::FETCH_COLUMN);
		});
	}

	/**
	 * @param mixed $default
	 * @return null|bool|string|int|float
	 */
	public function fetchValue($default = null) {
		return $this->createTempStatement(static function (QueryStatement $stmt) use ($default) {
			$result = $stmt->fetch(PDO::FETCH_NUM);
			if($result !== false) {
				return $result[0];
			}
			return $default;
		});
	}

	/**
	 * @return int
	 */
	public function getFoundRows(): int {
		return $this->foundRows;
	}

	/**
	 * @param callable $fn
	 * @return mixed
	 */
	private function createTempStatement(callable $fn) {
		$stmt = $this->createStatement();
		try {
			return $fn($stmt);
		} finally {
			$stmt->closeCursor();
		}
	}

	/**
	 * @return QueryStatement
	 */
	private function createStatement(): QueryStatement {
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
	 * @return Traversable|array[]
	 */
	public function getIterator() {
		/** @var Traversable|array[] $result */
		$result = $this->fetchRowsLazy();
		return $result;
	}

	/**
	 * @param Closure|null $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return mixed
	 */
	private function fetchAll(Closure $callback = null, int $mode = 0, $arg0 = null) {
		return $this->createTempStatement(function (QueryStatement $statement) use ($callback, $mode, $arg0) {
			$statement->setFetchMode($mode, $arg0);
			$data = $statement->fetchAll();
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				$data = array_map(static function ($row) use ($columnDefinitions) { return FieldValueConverter::convertValues($row, $columnDefinitions); }, $data);
			}
			if($callback !== null) {
				return call_user_func(static function ($resultData = []) use ($data, $callback) {
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
	 * @param Closure|null $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return Traversable|mixed[]
	 */
	private function fetchLazy(Closure $callback = null, int $mode = PDO::FETCH_ASSOC, $arg0 = null) {
		$statement = $this->createStatement();
		$statement->setFetchMode($mode, $arg0);
		$generator = new LazyRowGenerator($this->preserveTypes);
		return $generator->generate($statement, $callback);
	}

	/**
	 * @param Closure|null $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @param Closure|null $resultValidator
	 * @return mixed
	 */
	private function fetch(Closure $callback = null, int $mode = PDO::FETCH_ASSOC, $arg0 = null, Closure $resultValidator = null) {
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
