<?php
namespace Kir\MySQL\Builder\Traits;

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
		$this->having[] = array($expression, array_slice(func_get_args(), 1));
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
