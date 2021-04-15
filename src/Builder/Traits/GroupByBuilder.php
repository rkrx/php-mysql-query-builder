<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder;

trait GroupByBuilder {
	use AbstractDB;

	/** @var array<int, string> */
	private $groupBy = [];

	/**
	 * @param mixed ...$args
	 * @return $this
	 */
	public function groupBy(...$args) {
		foreach($args as $expression) {
			if(is_array($expression)) {
				if(!count($expression)) {
					continue;
				}
				$expression = $this->quoteExpr($expression[0], array_slice($expression, 1));
			}
			$this->groupBy[] = $expression;
		}
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildGroups(string $query): string {
		if(!count($this->groupBy)) {
			return $query;
		}
		$query .= "GROUP BY\n";
		$arr = [];
		foreach($this->groupBy as $expression) {
			$arr[] = "\t{$expression}";
		}
		return $query.implode(",\n", $arr)."\n";
	}

	/**
	 * @param string $expression
	 * @param array<int, null|int|float|string|array<int, string>|Builder\DBExpr|Builder\Select> $arguments
	 * @return string
	 */
	protected function quoteExpr(string $expression, array $arguments): string {
		return $this->db()->quoteExpression($expression, $arguments);
	}
}
