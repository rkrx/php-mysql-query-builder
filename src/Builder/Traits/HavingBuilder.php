<?php
namespace Kir\MySQL\Builder\Traits;

trait HavingBuilder {
	use AbstractDB;

	/**
	 * @var array
	 */
	private $having = array();

	/**
	 * @param string $expression
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
		if(!count($this->having)) {
			return $query;
		}
		$query .= "HAVING\n";
		$arr = array();
		foreach($this->having as $condition) {
			list($expression, $arguments) = $condition;
			$expr = $this->db()->quoteExpression($expression, $arguments);
			$arr[] = "\t({$expr})";
		}
		$query .= join("\n\tAND\n", $arr);
		return $query."\n";
	}
}