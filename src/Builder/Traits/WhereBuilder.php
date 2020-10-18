<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait WhereBuilder {
	use AbstractDB;
	use ConditionDefinition;

	/** @var array[] */
	private $where = [];

	/**
	 * @param string|array|OptionalExpression $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function where($expression, ...$args) {
		$fn = function (...$args) { $this->where[] = $args; };
		return $this->addCondition($fn, $expression, ...$args);
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildWhereConditions($query) {
		return ConditionBuilder::build($this->db(), $query, $this->where, 'WHERE');
	}
}
