<?php

namespace Kir\MySQL\Builder;

use DateTimeInterface;
use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Expr\OrderBySpecification;
use Kir\MySQL\Builder\Value\OptionalValue;
use Kir\MySQL\Tools\VirtualTable;

/**
 */
interface Select {
	/**
	 * @param bool $preserveTypes
	 * @return $this
	 */
	public function setPreserveTypes(bool $preserveTypes = true);

	/**
	 * @param bool $enabled
	 * @return $this
	 */
	public function forUpdate(bool $enabled = true);

	/**
	 * @param bool $distinct
	 * @return $this
	 */
	public function distinct(bool $distinct = true);

	/**
	 * @return array<int|string, string>
	 */
	public function getFields(): array;

	/**
	 * @param string|Select $expression
	 * @param string|null $alias
	 * @return $this
	 */
	public function field($expression, $alias = null);

	/**
	 * @param array<string, string>|array<int, string> $fields
	 * @return $this
	 */
	public function fields(array $fields);

	/**
	 * @param null|string $alias
	 * @param null|string|Select|VirtualTable|array<int, null|int|float|string|array<string, mixed>> $table
	 * @return $this
	 */
	public function from(?string $alias, $table = null);

	/**
	 * @param string $alias
	 * @param string|array<int, array<string, mixed>>|Select|VirtualTable $table
	 * @param string|null $expression
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select ...$args
	 * @return $this
	 */
	public function joinInner(string $alias, $table, ?string $expression = null, ...$args);

	/**
	 * @param string $alias
	 * @param string|array<int, array<string, mixed>>|Select|VirtualTable $table
	 * @param string $expression
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select ...$args
	 * @return $this
	 */
	public function joinLeft(string $alias, $table, string $expression, ...$args);

	/**
	 * @param string $alias
	 * @param string|array<int, array<string, mixed>>|Select|VirtualTable $table
	 * @param string $expression
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select ...$args
	 * @return $this
	 */
	public function joinRight(string $alias, $table, string $expression, ...$args);

	/**
	 * @param string|Select ...$queries
	 * @return $this
	 */
	public function union(...$queries);

	/**
	 * @param string|Select ...$queries
	 * @return $this
	 */
	public function unionAll(...$queries);

	/**
	 * @param string|array<string, mixed>|object|OptionalExpression $expression
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select|DateTimeInterface ...$args
	 * @return $this
	 */
	public function where($expression, ...$args);

	/**
	 * @param string|array<string, mixed>|object|OptionalExpression $expression
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select|DateTimeInterface ...$args
	 * @return $this
	 */
	public function having($expression, ...$args);

	/**
	 * @param mixed ...$args
	 * @return $this
	 */
	public function groupBy(...$args);

	/**
	 * @param string|OrderBySpecification $expression
	 * @param string&('ASC'|'DESC') $direction
	 * @return $this
	 */
	public function orderBy($expression, string $direction = 'ASC');

	/**
	 * @param string $fieldName
	 * @param array<int, int|float|string> $values
	 * @return $this
	 */
	public function orderByValues(string $fieldName, array $values);

	/**
	 * @param int|null|OptionalValue $limit
	 * @return $this
	 */
	public function limit($limit);

	/**
	 * @param int|null|OptionalValue $offset
	 * @return $this
	 */
	public function offset($offset = 0);

	/**
	 * @return bool
	 */
	public function getCalcFoundRows(): bool;

	/**
	 * @param bool $calcFoundRows
	 * @return $this
	 */
	public function setCalcFoundRows($calcFoundRows = true);

	/**
	 * @return string
	 */
	public function __toString(): string;
}
