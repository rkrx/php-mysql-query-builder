<?php

namespace Kir\MySQL\Builder\Expr;

/**
 * @deprecated
 */
class DBExprOrderBySpec implements OrderBySpecification {
	/** @var array<int, array{string, 'ASC'|'DESC'}> */
	private $fields = [];

	/**
	 * @param array<string|int, string> $spec Can be used to build a order-by-spec based on a key-value array of fields where keys represent an field alias and the value part represents an SQL-expression.
	 * @param array<int, array{string, 'ASC'|'DESC'}> $sortFieldsSpec Key value array where the key represents the field alias and the value is either 'ASC' or 'DESC'
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
						$direction,
					];
				}
			} else { // @phpstan-ignore-line
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
	 * @return array<int, array{string, string&('ASC'|'DESC')}>
	 */
	public function getFields(): array {
		return $this->fields;
	}
}
