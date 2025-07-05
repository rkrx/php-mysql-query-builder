<?php
namespace Kir\MySQL\Builder;

use Generator;
use IteratorAggregate;
use Kir\MySQL\Builder\Helpers\DBIgnoreRow;
use Kir\MySQL\Tools\AliasReplacer;
use Traversable;

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
	public function clearValues(): self;

	/**
	 * @param bool $preserveTypes
	 * @return $this
	 */
	public function setPreserveTypes(bool $preserveTypes = true): self;

	/**
	 * Fetches all rows using PDO::FETCH_NUM
	 *
	 * @template K of int
	 * @template V
	 * @param null|callable(array<string, null|scalar>): (void|DBIgnoreRow|array<K, V>) $callback
	 * @return ($callback is null ? list<array<int, null|scalar>> : array<int, array<K, V>>)
	 */
	public function fetchIndexedRows($callback = null): array;

	/**
	 * @template K
	 * @template V
	 * @param null|callable(array<string, null|scalar>): (void|DBIgnoreRow|array<K, V>) $callback
	 * @return ($callback is null ? array<int, array<string, null|scalar>> : array<int, array<K, V>>)
	 */
	public function fetchRows($callback = null): array;

	/**
	 * @template K
	 * @template V
	 * @param null|callable(array<string, null|scalar>): (void|DBIgnoreRow|array<K, V>) $callback
	 * @return ($callback is null ? Generator<int, array<string, null|scalar>> : Generator<int, array<K, V>>)
	 */
	public function fetchRowsLazy($callback = null);

	/**
	 * @template K
	 * @template V
	 * @param null|callable(array<string, mixed>): (array<K, V>|void|DBIgnoreRow) $callback
	 * @return ($callback is null ? array<string, null|scalar> : array<K, V>)
	 */
	public function fetchRow($callback = null): array;

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return ($callback is null ? array<int, T> : array<int, U>)
	 */
	public function fetchObjects(string $className = 'stdClass', $callback = null): array;

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return ($callback is null ? Generator<int, T> : Generator<int, U>)
	 */
	public function fetchObjectsLazy($className = null, $callback = null);

	/**
	 * @template T
	 * @template U
	 * @param class-string<T> $className
	 * @param null|callable(T): U $callback
	 * @return ($callback is null ? T : U)
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
	 * @param null|callable(null|scalar): T $fn
	 * @return ($fn is null ? array<int, null|scalar> : array<int, T>)
	 */
	public function fetchArray(?callable $fn = null): array;

	/**
	 * @template TDefault
	 * @template TCastFn
	 * @param TDefault $default
	 * @param null|callable(null|bool|string|int|float): TCastFn $fn
	 * @return ($fn is null ? null|scalar|TDefault : TCastFn)
	 */
	public function fetchValue($default = null, ?callable $fn = null);

	/**
	 * @return int
	 */
	public function getFoundRows(): int;

	/**
	 * @return Traversable<int, array<string, mixed>>
	 */
	public function getIterator(): Traversable;
}
