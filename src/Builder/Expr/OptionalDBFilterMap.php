<?php

namespace Kir\MySQL\Builder\Expr;

class OptionalDBFilterMap {
	/**
	 * @param array<string, mixed> $map
	 */
	final public function __construct(
		private array $map,
	) {}

	/**
	 * @param array<string, mixed> $map
	 * @return OptionalDBFilterMap
	 */
	public static function from(array $map) {
		return new static($map);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function getMap(): array {
		return $this->map;
	}

	/**
	 * @param string $expression
	 * @param string|string[] $keyPath
	 * @param null|callable(mixed): bool $validator
	 * @return DBExprFilter
	 */
	public function __invoke(string $expression, $keyPath, ?callable $validator = null): DBExprFilter {
		return new DBExprFilter($expression, $this->map, $keyPath, $validator);
	}
}
