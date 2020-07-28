<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OrderBySpecification;

trait OrderByBuilder {
	use AbstractDB;

	/** @var array */
	private $orderBy = [];

	/**
	 * @param string|OrderBySpecification $expression
	 * @param string $direction
	 * @return $this
	 */
	public function orderBy($expression, $direction = 'asc') {
		if($expression instanceof OrderBySpecification) {
			foreach($expression->getFields() as $field) {
				$this->addOrder($field[0], $field[1]);
			}
			return $this;
		}
		$this->addOrder($expression, $direction);
		return $this;
	}

	/**
	 * @param string $fieldName
	 * @param array $values
	 * @return $this
	 */
	public function orderByValues($fieldName, array $values) {
		$expr = [];
		foreach(array_values($values) as $idx => $value) {
			$expr[] = $this->db()->quoteExpression("WHEN ? THEN ?", [$value, $idx]);
		}
		$this->orderBy[] = [sprintf("CASE %s\n\t\t%s\n\tEND", $this->db()->quoteField($fieldName), implode("\n\t\t", $expr)), 'ASC'];
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
		$arr = [];
		foreach($this->orderBy as list($expression, $direction)) {
			$arr[] = sprintf("\t%s %s", $expression, strtoupper($direction));
		}
		return $query.implode(",\n", $arr)."\n";
	}

	/**
	 * @param string|array $expression
	 * @param string $direction
	 */
	private function addOrder($expression, $direction) {
		$direction = $this->fixDirection($direction);
		if(is_array($expression)) {
			if(!count($expression)) {
				return;
			}
			$arguments = [
				$expression[0],
				array_slice($expression, 1)
			];
			$expression = call_user_func_array([$this->db(), 'quoteExpression'], $arguments);
		}
		$this->orderBy[] = [$expression, $direction];
	}

	/**
	 * @param string $direction
	 * @return string
	 */
	private function fixDirection($direction) {
		return strtoupper($direction) !== 'ASC' ? 'DESC' : 'ASC';
	}
}
