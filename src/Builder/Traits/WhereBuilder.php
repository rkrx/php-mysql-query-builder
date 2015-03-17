<?php
namespace Kir\MySQL\Builder\Traits;

trait WhereBuilder {
	use AbstractDB;

	/**
	 * @var array
	 */
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
		if(!count($this->where)) {
			return $query;
		}
		$query .= "WHERE\n";
		$arr = array();
		foreach($this->where as $condition) {
			list($expression, $arguments) = $condition;
			$expr = $this->db()->quoteExpression($expression, $arguments);
			$arr[] = "\t({$expr})";
		}
		$query .= join("\n\tAND\n", $arr);
		return $query."\n";
	}
}