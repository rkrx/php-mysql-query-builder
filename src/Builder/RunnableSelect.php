<?php

namespace Kir\MySQL\Builder;

use Generator;
use IteratorAggregate;
use Kir\MySQL\Tools\AliasReplacer;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;

/**
 * @extends IteratorAggregate<int, array<string, mixed>>
 */
interface RunnableSelect extends IteratorAggregate {
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
	 * @return Generator<int, array<string, mixed>>
	 */
	public function getIterator();
}
