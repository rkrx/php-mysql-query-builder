<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait WhereBuilder {
	use AbstractDB;

	/** @var array */
	private $where = array();

	/**
	 * @param string|array $expression
	 * @param mixed ...$_
	 * @return $this
	 */
	public function where($expression, $_ = null) {
		if($expression instanceof OptionalExpression) {
			if($expression->isValid()) {
				$this->where[] = [$expression->getExpression(), $expression->getValue()];
			}
		} else {
			$this->where[] = [$expression, array_slice(func_get_args(), 1)];
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
