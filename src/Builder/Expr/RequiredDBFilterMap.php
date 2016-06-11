<?php
namespace Kir\MySQL\Builder\Expr;

class RequiredDBFilterMap {
	/** @var array */
	private $map;

	/**
	 * @param array $map
	 */
	public function __construct(array $map) {
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
	 * @param string $expression
	 * @param string|string[] $keyPath
	 * @param callable|null $validator
	 * @return DBExprFilter
	 */
	public function __invoke($expression, $keyPath, $validator = null) {
		return new DBExprFilter($expression, $this->map, $keyPath, $validator, function ($result, array $data) {
			if(!$result) {
				throw new RequiredValueNotFoundException(sprintf("Required value %s not found", $data['key']));
			}
		});
	}
}
