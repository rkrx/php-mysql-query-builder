<?php
namespace Kir\MySQL\Builder\Traits;

use DateTimeInterface;
use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Helpers\ConditionAddHelper;
use Kir\MySQL\Builder\Internal\ConditionBuilder;
use Kir\MySQL\Builder\Select;

trait HavingBuilder {
	use AbstractDB;

	/** @var array<int, array{string|array<string, mixed>|object|OptionalExpression, array<int, null|string|array<int, null|scalar>|DBExpr|Select>}> */
	private $having = [];

	/**
	 * @param string|array<string, mixed>|object|OptionalExpression $expression
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select|DateTimeInterface ...$args
	 * @return $this
	 */
	public function having($expression, ...$args) {
		$fn = function ($expression, $args) { $this->having[] = [$expression, $args]; };
		ConditionAddHelper::addCondition($fn, $expression, $args);
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
