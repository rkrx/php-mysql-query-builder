<?php
namespace Kir\MySQL\Builder;

use Closure;
use Generator;
use IteratorAggregate;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;
use Kir\MySQL\Builder\Helpers\FieldTypeProvider;
use Kir\MySQL\Builder\Helpers\FieldValueConverter;
use Kir\MySQL\Databases\MySQL;
use PDO;
use stdClass;
use Throwable;

/**
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
class RunnableSelect extends Select implements IteratorAggregate {
	/** @var array<string, mixed> */
	private $values = [];
	/** @var bool */
	private $preserveTypes;
	/** @var string */
	private $defaultClassName;
	/** @var int */
	private $foundRows = 0;

	/**
	 * @param MySQL $db
	 * @param array<string, mixed> $options
	 */
	public function __construct($db, array $options = []) {
		parent::__construct($db);
		$this->preserveTypes = array_key_exists('preserve-types-default', $options) ? $options['preserve-types-default'] : false;
		$this->defaultClassName = array_key_exists('fetch-object-class-default', $options) ? $options['fetch-object-class-default'] : stdClass::class;
	}

	/**
	 * @inheritDoc
	 */
	public function bindValues(array $values) {
		$this->values = array_merge($this->values, $values);
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function bindValue(string $key, $value) {
		$this->values[$key] = $value;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function clearValues() {
		$this->values = [];
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function setPreserveTypes(bool $preserveTypes = true) {
		$this->preserveTypes = $preserveTypes;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function fetchRows($callback = null): array {
		return $this->fetchAll($callback, PDO::FETCH_ASSOC);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchRowsLazy($callback = null) {
		$callback = $callback ?? (static function ($row) { return $row; });
		yield from $this->fetchLazy($callback, PDO::FETCH_ASSOC);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchRow($callback = null): array {
		$callback = $callback ?? (static function ($row) { return $row; });
		return $this->fetch($callback, PDO::FETCH_ASSOC, null, static function ($row) {
			return ['valid' => is_array($row), 'default' => []];
		});
	}

	/**
	 * @inheritDoc
	 */
	public function fetchObjects(string $className = 'stdClass', $callback = null): array {
		return $this->createTempStatement(function (QueryStatement $statement) use ($className, $callback) {
			$data = $statement->fetchAll(PDO::FETCH_CLASS, $className);
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
	 * @inheritDoc
	 */
	public function fetchObjectsLazy($className = null, $callback = null) {
		$callback = $callback ?? (static function ($row) { return $row; });
		yield from $this->fetchLazy($callback, PDO::FETCH_CLASS, $className ?: $this->defaultClassName);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchObject($className = null, $callback = null) {
		$callback = $callback ?? (static function ($row) { return $row; });
		return $this->fetch($callback, PDO::FETCH_CLASS, $className ?: $this->defaultClassName, static function ($row) {
			return ['valid' => is_object($row), 'default' => null];
		});
	}

	/**
	 * @inheritDoc
	 */
	public function fetchKeyValue($treatValueAsArray = false): array {
		return $this->createTempStatement(static function (QueryStatement $statement) use ($treatValueAsArray) {
			if($treatValueAsArray) {
				$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
				$result = [];
				foreach($rows as $row) {
					[$key] = array_values($row);
					$result[$key] = $row;
				}
				return $result;
			}
			return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function fetchGroups(array $fields): array {
		$rows = $this->fetchRows();
		$result = [];
		foreach($rows as $row) {
			/** @var array<string, mixed> $tmp */
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
	 * @inheritDoc
	 */
	public function fetchArray(?callable $fn = null): array {
		return $this->createTempStatement(static function (QueryStatement $stmt) use ($fn) {
			if($fn !== null) {
				return $stmt->fetchAll(PDO::FETCH_FUNC, $fn);
			}
			return $stmt->fetchAll(PDO::FETCH_COLUMN);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function fetchValue($default = null, ?callable $fn = null) {
		return $this->createTempStatement(static function (QueryStatement $stmt) use ($default, $fn) {
			$result = $stmt->fetchAll(PDO::FETCH_COLUMN);
			if($result !== false && array_key_exists(0, $result)) {
				return $fn !== null ? $fn($result[0]) : $result[0];
			}
			return $default;
		});
	}

	/**
	 * @inheritDoc
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
	 * @return Generator<int, array<string, mixed>>
	 */
	public function getIterator() {
		yield from $this->fetchRowsLazy();
	}

	/**
	 * @param null|callable(array<string, mixed>): (array<string, mixed>|DBIgnoreRow|null|void) $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return array<string, mixed>[]
	 */
	private function fetchAll($callback = null, int $mode = 0, $arg0 = null) {
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
						if($result instanceof DBIgnoreRow) {
							continue;
						}
						if($result !== null) {
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
	 * @template T
	 * @template U
	 * @param callable(T): U $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return Generator<int, T|U>
	 */
	private function fetchLazy($callback, int $mode = PDO::FETCH_ASSOC, $arg0 = null): Generator {
		$statement = $this->createStatement();
		$statement->setFetchMode($mode, $arg0);
		try {
			while($row = $statement->fetch()) {
				/** @var T $row */
//				if($this->preserveTypes) {
//					$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
//					$row = FieldValueConverter::convertValues($row, $columnDefinitions);
//				}
				$result = $callback($row);
				if($result instanceof DBIgnoreRow) {
					// Skip row in this case
					continue;
				}
				if($result !== null) {
					yield $result;
				} else {
					yield $row;
				}
			}
		} finally {
			try { $statement->closeCursor(); } catch (Throwable $e) {}
		}
	}

	/**
	 * @template T
	 * @template U
	 * @param callable(T): U $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @param Closure|null $resultValidator
	 * @return T|U|array<string, mixed>
	 */
	private function fetch($callback, int $mode = PDO::FETCH_ASSOC, $arg0 = null, Closure $resultValidator = null) {
		return $this->createTempStatement(function (QueryStatement $statement) use ($callback, $mode, $arg0, $resultValidator) {
			$statement->setFetchMode($mode, $arg0);
			$row = $statement->fetch();
			$result = $resultValidator === null ? ['valid' => true] : $resultValidator($row);
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
