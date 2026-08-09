<?php

namespace Kir\MySQL\Databases\MySQL;

use Closure;
use Generator;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;
use Kir\MySQL\Builder\Helpers\FieldTypeProvider;
use Kir\MySQL\Builder\Helpers\FieldValueConverter;
use Kir\MySQL\Builder\PreparedSelect;
use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Database;
use PDO;
use Throwable;

class MySQLPreparedSelect implements PreparedSelect {
	private int $foundRows = 0;

	/**
	 * @param Database $db
	 * @param QueryStatement $statement
	 * @param array<string, mixed> $values
	 * @param bool $preserveTypes
	 * @param class-string $defaultClassName
	 * @param bool $calcFoundRows
	 */
	public function __construct(
		private Database $db,
		private QueryStatement $statement,
		private array $values = [],
		private bool $preserveTypes = false,
		private string $defaultClassName = 'stdClass',
		private bool $calcFoundRows = false,
	) {}

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
	public function clearValues(): self {
		$this->values = [];

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function fetchIndexedRows($callback = null): array {
		return $this->fetchAll($callback, PDO::FETCH_NUM);
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
		$callback ??= static fn($row) => $row;
		yield from $this->fetchLazy($callback, PDO::FETCH_ASSOC);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchRow($callback = null): array {
		$callback ??= static fn($row) => $row;

		return $this->fetch($callback, PDO::FETCH_ASSOC, null, static fn($row) => [
			'valid' => is_array($row),
			'default' => [],
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchObjects(string $className = 'stdClass', $callback = null): array {
		return $this->createTempStatement(function(QueryStatement $statement) use ($className, $callback) {
			$data = $statement->fetchAll(PDO::FETCH_CLASS, $className);
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				$data = array_map(static function($row) use ($columnDefinitions) {
					$values = FieldValueConverter::convertValues(get_object_vars($row), $columnDefinitions);
					foreach($values as $key => $value) {
						$row->{$key} = $value;
					}

					return $row;
				}, $data);
			}
			if($callback !== null) {
				return call_user_func(static function($resultData = []) use ($data, $callback) {
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
		$callback ??= static fn($row) => $row;
		yield from $this->fetchLazy($callback, PDO::FETCH_CLASS, $className ?: $this->defaultClassName);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchObject($className = null, $callback = null) {
		$callback ??= static fn($row) => $row;

		return $this->fetch($callback, PDO::FETCH_CLASS, $className ?: $this->defaultClassName, static fn($row) => [
			'valid' => is_object($row),
			'default' => null,
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchKeyValue($treatValueAsArray = false): array {
		return $this->createTempStatement(static function(QueryStatement $statement) use ($treatValueAsArray) {
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
	public function fetchValues(?callable $fn = null): array {
		return $this->createTempStatement(static function(QueryStatement $stmt) use ($fn) {
			if($fn !== null) {
				return $stmt->fetchAll(PDO::FETCH_FUNC, $fn);
			}

			return $stmt->fetchAll(PDO::FETCH_COLUMN);
		});
	}

	/**
	 * @inheritDoc
	 * @deprecated Use fetchValues() instead.
	 */
	public function fetchArray(?callable $fn = null): array {
		return $this->fetchValues($fn);
	}

	/**
	 * @inheritDoc
	 */
	public function fetchValue($default = null, ?callable $fn = null) {
		return $this->createTempStatement(static function(QueryStatement $stmt) use ($default, $fn) {
			$result = $stmt->fetchAll(PDO::FETCH_COLUMN);
			if(array_key_exists(0, $result)) {
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
	 * @param callable(QueryStatement): mixed $fn
	 * @return mixed
	 */
	private function createTempStatement(callable $fn) {
		try {
			$this->execute();

			return $fn($this->statement);
		} finally {
			$this->statement->closeCursor();
		}
	}

	private function execute(): void {
		$this->statement->execute($this->values);
		if($this->calcFoundRows) {
			$this->foundRows = (int) $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();
		}
	}

	/**
	 * @param null|callable $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return array<int, mixed>
	 */
	private function fetchAll($callback = null, int $mode = 0, $arg0 = null): array {
		return $this->createTempStatement(function(QueryStatement $statement) use ($callback, $mode, $arg0) {
			$statement->setFetchMode($mode, $arg0);
			$data = $statement->fetchAll(PDO::FETCH_DEFAULT);
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				if($mode === PDO::FETCH_NUM) {
					$columnDefinitions = array_values($columnDefinitions);
				}
				$data = array_map(static fn($row) => FieldValueConverter::convertValues($row, $columnDefinitions), $data);
			}
			if($callback !== null) {
				return call_user_func(static function($resultData = []) use ($data, $callback) {
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
	 * @param null|callable(T): U $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return ($callback is null ? Generator<int, T> : Generator<int, U>)
	 */
	private function fetchLazy($callback = null, int $mode = PDO::FETCH_ASSOC, $arg0 = null): Generator {
		$this->execute();
		$this->statement->setFetchMode($mode, $arg0);
		try {
			while($row = $this->statement->fetch(PDO::FETCH_DEFAULT)) {
				/** @var T $row */
				$result = $callback($row);
				if($result instanceof DBIgnoreRow) {
					continue;
				}
				if($result !== null) {
					yield $result;
				} else {
					yield $row;
				}
			}
		} finally {
			try {
				$this->statement->closeCursor();
			} catch(Throwable) {
			}
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
	private function fetch($callback, int $mode = PDO::FETCH_ASSOC, $arg0 = null, ?Closure $resultValidator = null) {
		return $this->createTempStatement(function(QueryStatement $statement) use ($callback, $mode, $arg0, $resultValidator) {
			$statement->setFetchMode($mode, $arg0);
			$row = $statement->fetch(PDO::FETCH_DEFAULT);
			$result = $resultValidator === null ? ['valid' => true] : $resultValidator($row);
			if(!$result['valid']) {
				return $result['default'];
			}
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				if(is_object($row)) {
					$values = FieldValueConverter::convertValues(get_object_vars($row), $columnDefinitions);
					foreach($values as $key => $value) {
						$row->{$key} = $value;
					}
				} else {
					$row = FieldValueConverter::convertValues($row, $columnDefinitions);
				}
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
