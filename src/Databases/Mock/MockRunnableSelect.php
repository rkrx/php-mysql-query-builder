<?php

namespace Kir\MySQL\Databases\Mock;

use Kir\MySQL\Builder\Helpers\DBIgnoreRow;
use Kir\MySQL\Databases\MySQL\MySQLSelect;
use stdClass;
use Traversable;

class MockRunnableSelect extends MySQLSelect {
	/** @var array<string, mixed> */
	private array $values = [];
	private bool $preserveTypes = false;
	private int $foundRows = 0;

	public function bindValues(array $values) {
		$this->values = array_merge($this->values, $values);

		return $this;
	}

	public function bindValue(string $key, $value) {
		$this->values[$key] = $value;

		return $this;
	}

	public function clearValues(): self {
		$this->values = [];

		return $this;
	}

	public function setPreserveTypes(bool $preserveTypes = true): self {
		$this->preserveTypes = $preserveTypes;

		return $this;
	}

	public function fetchIndexedRows($callback = null): array {
		$rows = $this->consumeRows();
		$rows = array_map(static fn($row) => array_values($row), $rows);

		return $this->applyCallback($rows, $callback);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function consumeRows(): array {
		/** @var MockDatabase $db */
		$db = $this->db();
		$rows = $db->popDQL();
		$rows = is_array($rows) ? $rows : [];
		$this->foundRows = count($rows);

		return $rows;
	}

	/**
	 * @template TRow
	 * @template TResult
	 * @param array<int, TRow> $rows
	 * @param null|callable(TRow): (TResult|TRow|DBIgnoreRow|null) $callback
	 * @return array<int, TRow|TResult>
	 */
	private function applyCallback(array $rows, $callback): array {
		if($callback === null) {
			return $rows;
		}
		$result = [];
		foreach($rows as $row) {
			$tmp = $callback($row);
			if($tmp instanceof DBIgnoreRow) {
				continue;
			}
			$result[] = $tmp ?? $row;
		}

		return $result;
	}

	public function fetchAll($callback = null): array {
		return $this->fetchRows($callback);
	}

	public function fetchRows($callback = null): array {
		return $this->applyCallback($this->consumeRows(), $callback);
	}

	public function fetchRow($callback = null): array {
		$rows = $this->consumeRows();
		$row = $rows[0] ?? [];

		return $this->applySingleCallback($row, $callback);
	}

	/**
	 * @template TRow
	 * @template TResult
	 * @param TRow $row
	 * @param null|callable(TRow): (TResult|TRow|null) $callback
	 * @return TRow|TResult
	 */
	private function applySingleCallback($row, $callback) {
		if($callback === null) {
			return $row;
		}
		$result = $callback($row);

		return $result ?? $row;
	}

	public function fetchObjects(string $className = 'stdClass', $callback = null): array {
		$objects = [];
		foreach($this->consumeRows() as $row) {
			$objects[] = $this->rowToObject($row, $className);
		}

		return $this->applyCallback($objects, $callback);
	}

	/**
	 * @param array<string, mixed> $row
	 * @param class-string $className
	 * @return object
	 */
	private function rowToObject(array $row, string $className): object {
		$object = new $className();
		foreach($row as $key => $value) {
			$object->{$key} = $value;
		}

		return $object;
	}

	public function fetchObjectsLazy($className = null, $callback = null) {
		$className ??= stdClass::class;
		$callback ??= static fn($row) => $row;
		foreach($this->consumeRows() as $row) {
			$object = $this->rowToObject($row, $className);
			$result = $callback($object);
			if($result instanceof DBIgnoreRow) {
				continue;
			}
			yield $result ?? $object;
		}
	}

	public function fetchObject($className = null, $callback = null) {
		$className ??= stdClass::class;
		$rows = $this->consumeRows();
		$object = $this->rowToObject($rows[0] ?? [], $className);

		return $this->applySingleCallback($object, $callback);
	}

	public function fetchKeyValue($treatValueAsArray = false): array {
		$result = [];
		foreach($this->consumeRows() as $row) {
			[$key, $value] = array_values($row);
			$result[$key] = $treatValueAsArray ? $row : $value;
		}

		return $result;
	}

	public function fetchGroups(array $fields): array {
		$rows = $this->consumeRows();
		$result = [];
		foreach($rows as $row) {
			$bucket = &$result;
			foreach($fields as $field) {
				$value = (string) ($row[$field] ?? '');
				if(!isset($bucket[$value]) || !is_array($bucket[$value])) {
					$bucket[$value] = [];
				}
				$bucket = &$bucket[$value];
			}
			$bucket[] = $row;
		}

		return $result;
	}

	public function fetchArray(?callable $fn = null): array {
		$values = [];
		foreach($this->consumeRows() as $row) {
			$value = array_values($row)[0] ?? null;
			$values[] = $fn ? $fn($value) : $value;
		}

		return $values;
	}

	public function fetchValue($default = null, ?callable $fn = null) {
		$rows = $this->consumeRows();
		$value = array_values($rows[0] ?? [])[0] ?? $default;

		return $fn ? $fn($value) : $value;
	}

	public function getFoundRows(): int {
		return $this->foundRows;
	}

	public function getIterator(): Traversable {
		return $this->fetchRowsLazy();
	}

	public function fetchRowsLazy($callback = null) {
		$callback ??= static fn($row) => $row;
		foreach($this->consumeRows() as $row) {
			$result = $callback($row);
			if($result instanceof DBIgnoreRow) {
				continue;
			}
			yield $result ?? $row;
		}
	}
}
