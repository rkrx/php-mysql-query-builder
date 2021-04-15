<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Helpers\ConditionAddHelper;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait WhereBuilder {
	use AbstractDB;

	/** @var array<int, array{string|array<string, mixed>|object|OptionalExpression, array<int, null|string|array<int, string>|Builder\DBExpr|Builder\Select>}> */
	private $where = [];

	/**
	 * @param string|array<string, mixed>|object|OptionalExpression $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function where($expression, ...$args): self {
		$fn = function ($expression, $args) { $this->where[] = [$expression, $args]; };
		ConditionAddHelper::addCondition($fn, $expression, $args);
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildWhereConditions(string $query): string {
		return ConditionBuilder::build($this->db(), $query, $this->where, 'WHERE');
	}
}
