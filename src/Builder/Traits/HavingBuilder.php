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
	 * @param mixed[] $args
	 * @return $this
	 */
	public function having($expression, ...$args) {
		if($expression instanceof OptionalExpression) {
			if($expression->isValid()) {
				$this->having[] = [$expression->getExpression(), $expression->getValue()];
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
