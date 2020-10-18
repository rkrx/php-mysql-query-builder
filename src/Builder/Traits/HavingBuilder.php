<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait HavingBuilder {
	use AbstractDB;
	use ConditionDefinition;

	/** @var array[] */
	private $having = [];

	/**
	 * @param string|array|object|OptionalExpression $expression
	 * @param mixed[] $args
	 * @return $this
	 */
	public function having($expression, ...$args) {
		$fn = function (...$args) { $this->having[] = $args; };
		return $this->addCondition($fn, $expression, ...$args);
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildHavingConditions($query) {
		return ConditionBuilder::build($this->db(), $query, $this->having, 'HAVING');
	}
}
