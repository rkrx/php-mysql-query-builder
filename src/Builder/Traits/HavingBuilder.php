<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait HavingBuilder {
	use AbstractDB;

	/** @var array */
	private $having = [];

	/**
	 * @param string|array|OptionalExpression $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function having($expression, ...$args) {
		if($expression instanceof OptionalExpression) {
			if($expression->isValid()) {
				$this->having[] = [$expression->getExpression(), $expression->getValue()];
			}
		} elseif(is_array($expression) || is_object($expression)) {
			if(is_object($expression)) {
				$expression = (array) $expression;
			}
			if(count($expression) > 0) {
				$this->having[] = [$expression, array_slice(func_get_args(), 1)];
			}
		} else {
			$this->having[] = [$expression, array_slice(func_get_args(), 1)];
		}
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildHavingConditions($query) {
		return ConditionBuilder::build($this->db(), $query, $this->having, 'HAVING');
	}
}
