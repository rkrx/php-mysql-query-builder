<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait WhereBuilder {
	use AbstractDB;

	/** @var array<int, mixed> */
	private $where = [];
	
	/**
	 * @param string|array|OptionalExpression $expression
	 * @param array<int, mixed> $args
	 * @return $this
	 */
	public function where($expression, ...$args) {
		if($expression instanceof OptionalExpression) {
			if($expression->isValid()) {
				$this->where[] = [$expression->getExpression(), $expression->getValue()];
			}
		} else {
			$this->where[] = [$expression, $args];
		}
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildWhereConditions($query) {
		return ConditionBuilder::build($this->db(), $query, $this->where, 'WHERE');
	}
}
