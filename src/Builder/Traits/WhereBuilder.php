<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Internal\ConditionBuilder;

trait WhereBuilder {
	use AbstractDB;

	/** @var array */
	private $where = array();

	/**
	 * @param string $expression
	 * @param mixed ...$param
	 * @return $this
	 */
	public function where($expression, $param = null) {
		$this->where[] = array($expression, array_slice(func_get_args(), 1));
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