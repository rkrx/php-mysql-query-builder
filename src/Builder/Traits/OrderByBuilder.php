<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OrderBySpecification;

trait OrderByBuilder {
	use AbstractDB;

	/** @var array<int, array{string, string}> */
	private $orderBy = [];

	/**
	 * @param string|OrderBySpecification $expression
	 * @param string&('ASC'|'DESC') $direction
	 * @return $this
	 */
	public function orderBy($expression, string $direction = 'ASC') {
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
	 * @param array<int, int|float|string> $values
	 * @return $this
	 */
	public function orderByValues(string $fieldName, array $values) {
		$expr = [];
		foreach(array_values($values) as $idx => $value) {
			$expr[] = $this->db()->quoteExpression("WHEN ? THEN ?", [$value, $idx]);
		}
		$this->orderBy[] = [sprintf("CASE %s\n\t\t%s\n\tEND", $this->db()->quoteField($fieldName), implode("\n\t\t", $expr)), 'ASC'];
		return $this;
	}

	/**
	 * @return array<int, array{string, string}>
	 */
	public function getOrderBy() {
		return $this->orderBy;
	}

	/**
	 * Removed all order information from the current layer. This dies not affect sub-selects.
	 *
	 * @return $this
	 */
	public function resetOrderBy() {
		$this->orderBy = [];
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildOrder(string $query): string {
		if(!count($this->orderBy)) {
			return $query;
		}
		$query .= "ORDER BY\n";
		$arr = [];
		foreach($this->orderBy as [$expression, $direction]) {
			$arr[] = sprintf("\t%s %s", $expression, strtoupper($direction));
		}
		return $query.implode(",\n", $arr)."\n";
	}

	/**
	 * @param string|array<int, mixed> $expression
	 * @param string&('ASC'|'DESC') $direction
	 */
	private function addOrder($expression, string $direction): void {
		$direction = $this->fixDirection($direction);
		if(is_array($expression)) {
			if(count($expression) < 1) {
				return;
			}
			$expr = (string) $expression[0];
			$expression = $this->db()->quoteExpression($expr, array_slice($expression, 1));
		}
		$this->orderBy[] = [$expression, $direction];
	}

	/**
	 * @param string $direction
	 * @return string&('ASC'|'DESC')
	 */
	private function fixDirection(string $direction): string {
		return strtoupper($direction) !== 'ASC' ? 'DESC' : 'ASC';
	}
}
