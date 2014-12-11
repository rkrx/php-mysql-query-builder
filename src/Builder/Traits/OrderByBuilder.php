<?php
namespace Kir\MySQL\Builder\Traits;

trait OrderByBuilder {
	use AbstractDB;

	/**
	 * @var array
	 */
	private $orderBy = array();

	/**
	 * @param string $expression
	 * @param string $direction
	 * @return $this
	 */
	public function orderBy($expression, $direction = 'asc') {
		if(strtolower($direction) != 'desc') {
			$direction = 'ASC';
		}
		if(is_array($expression)) {
			if(!count($expression)) {
				return $this;
			}
			$arguments = array(
				$expression[0],
				array_slice($expression, 1)
			);
			$expression = call_user_func_array(array($this->db(), 'quoteExpression'), $arguments);
		}
		$this->orderBy[] = array($expression, $direction);
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildOrder($query) {
		if(!count($this->orderBy)) {
			return $query;
		}
		$query .= "ORDER BY\n";
		$arr = array();
		foreach($this->orderBy as $order) {
			list($expression, $direction) = $order;
			$arr[] = sprintf("\t%s %s", $expression, strtoupper($direction));
		}
		return $query.join(",\n", $arr)."\n";
	}
}