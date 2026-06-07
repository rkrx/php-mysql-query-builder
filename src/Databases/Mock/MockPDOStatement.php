<?php

namespace Kir\MySQL\Databases\Mock;

use PDO;
use PDOStatement;
use stdClass;

/**
 * Minimal PDOStatement mock used by {@see MockDatabaseStatement}.
 * It translates fetch* calls into data taken from a test data stack.
 */
class MockPDOStatement extends PDOStatement {
	/** @var callable */
	private $popper;
	private string $type;
	private int $rowCount = 0;
	/** @var array<int, array<string, mixed>> */
	private array $data = [];
	private int $pointer = 0;
	private int $fetchMode = PDO::FETCH_ASSOC;
	private mixed $fetchArgument = null;
	/** @var array<int, mixed> */
	private array $ctorArgs = [];

	/**
	 * @param callable $popper Returns the next stack entry when executed
	 * @param string $type dql|dml|ddl
	 */
	public function __construct(callable $popper, string $type) {
		$this->popper = $popper;
		$this->type = $type;
	}

	/**
	 * @param array<int|string, mixed>|null $params
	 */
	public function execute(?array $params = null): bool {
		if($this->type === 'dql') {
			$result = ($this->popper)();
			$this->data = is_array($result) ? $result : [];
			$this->rowCount = count($this->data);
			$this->pointer = 0;
		} else {
			$result = ($this->popper)();
			$this->rowCount = (int) $result;
		}

		return true;
	}

	public function setFetchMode(int $mode, ...$args): true {
		$this->fetchMode = $mode;
		$this->fetchArgument = $args[0] ?? null;
		$this->ctorArgs = isset($args[1]) && is_array($args[1]) ? $args[1] : [];

		return true;
	}

	/**
	 * @return array<int, mixed>
	 */
	public function fetchAll(int $mode = PDO::FETCH_DEFAULT, ...$args): array {
		$mode = $mode === PDO::FETCH_DEFAULT ? $this->fetchMode : $mode;
		$fetchArgument = $args[0] ?? null;
		$ctorArgs = $args[1] ?? [];

		if($mode === PDO::FETCH_COLUMN) {
			$column = (int) ($fetchArgument ?? 0);

			return array_map(function($row) use ($column) {
				return $this->valueByIndex($row, $column);
			}, $this->data);
		}

		if($mode === PDO::FETCH_KEY_PAIR) {
			$result = [];
			foreach($this->data as $row) {
				[$k, $v] = array_values($row);
				$result[$k] = $v;
			}

			return $result;
		}

		return array_map(function($row) use ($mode, $fetchArgument, $ctorArgs) {
			return $this->transformRow($row, $mode, $fetchArgument, $ctorArgs);
		}, $this->data);
	}

	/**
	 * @param array<string, mixed> $row
	 * @param int $index
	 * @return mixed
	 */
	private function valueByIndex(array $row, int $index): mixed {
		$values = array_values($row);

		return $values[$index] ?? null;
	}

	/**
	 * @param array<string, mixed> $row
	 * @param int $mode
	 * @param mixed $arg
	 * @param array<int, mixed> $ctorArgs
	 * @return mixed
	 */
	private function transformRow(array $row, int $mode, $arg, array $ctorArgs): mixed {
		return match ($mode) {
			PDO::FETCH_NUM => array_values($row),
			PDO::FETCH_COLUMN => $this->valueByIndex($row, (int) ($arg ?? 0)),
			PDO::FETCH_CLASS => $this->buildObject($row, $arg ?: stdClass::class, $ctorArgs),
			default => $row,
		};
	}

	/**
	 * @param array<string, mixed> $row
	 * @param class-string $className
	 * @param array<int, mixed> $ctorArgs
	 * @return object
	 */
	private function buildObject(array $row, string $className, array $ctorArgs): object {
		$object = new $className(...$ctorArgs);
		foreach($row as $key => $value) {
			$object->{$key} = $value;
		}

		return $object;
	}

	public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
		if($this->pointer >= count($this->data)) {
			return false;
		}
		$row = $this->data[$this->pointer++];
		$mode = $mode === PDO::FETCH_DEFAULT ? $this->fetchMode : $mode;

		return $this->transformRow($row, $mode, $this->fetchArgument, $this->ctorArgs);
	}

	public function fetchColumn(int $column = 0): mixed {
		if($this->pointer >= count($this->data)) {
			return false;
		}
		$row = $this->data[$this->pointer++];

		return $this->valueByIndex($row, $column);
	}

	public function closeCursor(): bool {
		$this->pointer = 0;

		return true;
	}

	public function columnCount(): int {
		return count($this->data ? array_keys($this->data[0]) : []);
	}

	public function rowCount(): int {
		return $this->rowCount;
	}

	/**
	 * @return array{name: string, table?: string, native_type?: string, len: int, flags: array<int, string>, precision: int, pdo_type: int}|false
	 */
	public function getColumnMeta(int $column): array|false {
		$first = $this->data[0] ?? null;
		if($first === null) {
			return false;
		}
		$keys = array_keys($first);
		if(!array_key_exists($column, $keys)) {
			return false;
		}

		return [
			'name' => $keys[$column],
			'len' => 0,
			'flags' => [],
			'precision' => 0,
			'pdo_type' => PDO::PARAM_STR,
		];
	}
}
