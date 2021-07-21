<?php
namespace Kir\MySQL\Builder\Expr;

/**
 * @deprecated
 */
class DBExprOrderBySpec implements OrderBySpecification {
	/** @var array<int, array{string, string}> */
	private $fields = [];

	/**
	 * @param array<string|int, string> $spec
	 * @param array<int, array{string, string}|array<string, string>> $sortFieldsSpec
	 */
	public function __construct(array $spec, array $sortFieldsSpec) {
		$expressions = [];
		foreach($spec as $specReference => $dbExpr) {
			if(is_int($specReference)) {
				$specReference = $dbExpr;
			}
			$expressions[$specReference] = $dbExpr;
		}
		foreach($sortFieldsSpec as $sortFieldSpec) {
			if(array_key_exists(0, $sortFieldSpec)) {
				if(array_key_exists($sortFieldSpec[0], $expressions)) {
					$direction = 'ASC';
					if(array_key_exists(1, $sortFieldSpec) && strtoupper($sortFieldSpec[1]) !== 'ASC') {
						$direction = 'DESC';
					}
					$this->fields[] = [
						$expressions[$sortFieldSpec[0]],
						$direction
					];
				}
			} else {
				foreach($sortFieldSpec as $alias => $direction) {
					$direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
					if(array_key_exists($alias, $expressions)) {
						$this->fields[] = [$expressions[$alias], $direction];
					}
				}
			}
		}
	}

	/**
	 * Returns an array[], where each value is a [db-expression, sort-direction]
	 * The sort-direction can be either ASC or DESC
	 *
	 * @return array<int, array{string, 'ASC'|'DESC'}>
	 */
	public function getFields(): array {
		return $this->fields;
	}
}
