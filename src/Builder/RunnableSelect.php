<?php
namespace Kir\MySQL\Builder;

use Generator;
use IteratorAggregate;
use Kir\MySQL\Tools\AliasReplacer;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;

/**
 * @extends IteratorAggregate<int, array<string, mixed>>
 */
interface RunnableSelect extends Select, IteratorAggregate {
	/**
	 * @return AliasReplacer
	 */
	public function aliasReplacer(): AliasReplacer;

	/**
	 * @param array<string, mixed> $values
	 * @return $this
	 */
	public function bindValues(array $values);

	/**
	 * @param string $key
	 * @param string|int|bool|float|null $value
	 * @return $this
	 */
	public function bindValue(string $key, $value);

	/**
	 * @return $this
	 */
	public function clearValues();

	/**
	 * @param bool $preserveTypes
	 * @return $this
	 */
	public function setPreserveTypes(bool $preserveTypes = true);

	/**
	 * @param null|callable(array<string, mixed>): array<string, mixed>|callable(array<string, mixed>): void|callable(array<string, mixed>): DBIgnoreRow $callback
	 * @return array<int, array<string, mixed>>
	 */
	public function fetchRows($callback = null): array;

	/**
	 * @param null|callable(array<string, mixed>): (array<mixed, mixed>|null|void) $callback
	 * @return Generator<int, array<string, mixed>>
	 */
	public function fetchRowsLazy($callback = null);

	/**
	 * @param null|callable(array<string, mixed>): array<string, mixed>|callable(array<string, mixed>): void|callable(array<string, mixed>): DBIgnoreRow $callback
	 * @return array<string, mixed>
	 */
	public function fetchRow($callback = null): array;

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return T[]|U[]
	 */
	public function fetchObjects(string $className = 'stdClass', $callback = null): array;

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return Generator<int, T|U>
	 */
	public function fetchObjectsLazy($className = null, $callback = null);

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return T|U
	 */
	public function fetchObject($className = null, $callback = null);

	/**
	 * @param bool $treatValueAsArray
	 * @return array<mixed, mixed>
	 */
	public function fetchKeyValue($treatValueAsArray = false): array;

	/**
	 * @param string[] $fields
	 * @return array<string, array<int, mixed>>
	 */
	public function fetchGroups(array $fields): array;

	/**
	 * @template T
	 * @param null|callable(null|bool|int|float|string): T $fn
	 * @return array<int, T|string>
	 */
	public function fetchArray(?callable $fn = null): array;

	/**
	 * @template TDefault
	 * @template TCastFn
	 * @param TDefault $default
	 * @param null|callable(null|bool|string|int|float): TCastFn $fn
	 * @return null|bool|string|int|float|TDefault|TCastFn
	 */
	public function fetchValue($default = null, ?callable $fn = null);

	/**
	 * @return int
	 */
	public function getFoundRows(): int;

	/**
	 * @return Traversable|array[]
	 */
	public function getIterator() {
		/** @var Traversable|array[] $result */
		$result = $this->fetchRowsLazy();
		return $result;
	}

	/**
	 * @param Closure $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return mixed
	 */
	private function fetchAll(Closure $callback = null, $mode, $arg0 = null) {
		return $this->createTempStatement(function (QueryStatement $statement) use ($callback, $mode, $arg0) {
			$statement->setFetchMode($mode, $arg0);
			$data = $statement->fetchAll();
			if($this->preserveTypes) {
				$columnDefinitions = FieldTypeProvider::getFieldTypes($statement);
				$data = array_map(static function ($row) use ($columnDefinitions) {
					return FieldValueConverter::convertValues($row, $columnDefinitions);
				}, $data);
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
	 * @param Closure $callback
	 * @param int $mode
	 * @param mixed $arg0
	 * @return Traversable|mixed[]
	 */
	private function fetchLazy(Closure $callback = null, $mode, $arg0 = null) {
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
