<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait HavingBuilder {
	use AbstractDB;

	/** @var array */
	private $having = array();

	/**
	 * @param string $expression
	 * @param mixed ...$param
	 * @return $this
	 */
	public function having($expression) {
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
