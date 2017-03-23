<?php
namespace Kir\MySQL\Builder\Expr;

class DBExprOrderBySpec implements OrderBySpecification {
	/** @var array[] */
	private $fields = [];
	
	/**
	 * @param $spec
	 * @param $sortFieldsSpec
	 */
	public function __construct($spec, $sortFieldsSpec) {
		$expressions = [];
		foreach($spec as $specReference => $dbExpr) {
			if(is_int($specReference)) {
				$specReference = $dbExpr;
			}
			$expressions[$specReference] = $dbExpr;
		}
		foreach($sortFieldsSpec as $sortFieldSpec) {
			if(array_key_exists(0, $sortFieldSpec) && array_key_exists($sortFieldSpec[0], $expressions)) {
				$direction = 'ASC';
				if(array_key_exists(1, $sortFieldSpec) && strtoupper($sortFieldSpec[1]) !== 'ASC') {
					$direction = 'DESC';
				}
				$this->fields[] = [
					$expressions[$sortFieldSpec[0]],
					$direction
				];
			}
		}
	}
	
	/**
	 * Returns an array[], where each value is a [db-expression, sort-direction]
	 * The sort-direction can be either ASC or DESC
	 *
	 * @return array[]
	 */
	public function getFields() {
		return $this->fields;
	}
}
