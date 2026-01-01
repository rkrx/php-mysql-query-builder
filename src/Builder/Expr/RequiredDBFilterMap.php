<?php

namespace Kir\MySQL\Builder\Expr;

use Kir\MySQL\Builder\Helpers\RecursiveStructureAccess;

class RequiredDBFilterMap {
	/**
	 * @param array<string, mixed> $map
	 */
	final public function __construct(
		private array $map,
	) {}

	/**
	 * @param array<string, mixed> $map
	 * @return RequiredDBFilterMap
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
	public function __invoke(string $expression, $keyPath, $validator = null) {
		if(!RecursiveStructureAccess::recursiveHas($this->map, $keyPath)) {
			throw new RequiredValueNotFoundException(sprintf("Required value %s not found", json_encode($keyPath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
		}

		return new DBExprFilter($expression, $this->map, $keyPath, $validator);
	}
}
