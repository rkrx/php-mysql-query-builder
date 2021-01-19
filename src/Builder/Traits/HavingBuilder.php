<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Helpers\ConditionAddHelper;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait HavingBuilder {
	use AbstractDB;

	/** @var array[] */
	private $having = [];

	/**
	 * @param string|array|object|OptionalExpression $expression
	 * @param mixed[] $args
	 * @return $this
	 */
	public function having($expression, ...$args) {
		$fn = function (...$args) { $this->having[] = $args; };
		ConditionAddHelper::addCondition($fn, $expression, ...$args);
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildHavingConditions(string $query): string {
		return ConditionBuilder::build($this->db(), $query, $this->having, 'HAVING');
	}
}
