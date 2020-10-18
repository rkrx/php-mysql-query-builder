<?php
namespace Kir\MySQL\Builder\Traits;

use Closure;
use Kir\MySQL\Builder\Expr\OptionalExpression;

trait ConditionDefinition {
	/**
	 * @param Closure $addFn
	 * @param string|array|object|OptionalExpression $expression
	 * @param mixed[] $args
	 * @return $this
	 */
	protected function addCondition(Closure $addFn, $expression, ...$args) {
		if($expression instanceof OptionalExpression) {
			if($expression->isValid()) {
				$addFn($expression->getExpression(), $expression->getValue());
			}
		} elseif(is_object($expression)) {
			$this->addAsArray($addFn, (array) $expression, $args);
		} elseif(is_array($expression)) {
			$this->addAsArray($addFn, $expression, $args);
		} else {
			$addFn($expression, $args);
		}
		return $this;
	}

	/**
	 * @param Closure $addFn
	 * @param array $expression
	 * @param array $args
	 */
	private function addAsArray(Closure $addFn, array $expression, array $args) {
		if(count($expression) > 0) {
			$addFn($expression, $args);
		}
	}
}
