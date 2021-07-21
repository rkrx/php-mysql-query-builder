<?php
namespace Kir\MySQL\Builder\Expr;

use Kir\MySQL\Builder\Helpers\RecursiveStructureAccess;

class RequiredDBFilterMap {
	/** @var array */
	private $map;

	/**
	 * @param array $map
	 */
	final public function __construct(array $map) {
		$this->map = $map;
	}

	/**
	 * @param array $map
	 * @return static
	 */
	public static function from(array $map) {
		return new static($map);
	}

	/**
	 * @return array
	 */
	protected function getMap(): array {
		return $this->map;
	}

	/**
	 * @param string $expression
	 * @param string|string[] $keyPath
	 * @param callable|null $validator
	 * @return DBExprFilter
	 */
	public function __invoke($expression, $keyPath, $validator = null) {
		if(!RecursiveStructureAccess::recursiveHas($this->map, $keyPath)) {
			throw new RequiredValueNotFoundException(sprintf("Required value %s not found", json_encode($keyPath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
		}
		return new DBExprFilter($expression, $this->map, $keyPath, $validator);
	}
}
