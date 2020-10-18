<?php
namespace Kir\MySQL\Builder\Helpers;

use Closure;
use Kir\MySQL\Builder\Expr\OptionalExpression;

abstract class ConditionAddHelper {
	/**
	 * @param Closure $addFn
	 * @param string|array|object|OptionalExpression $expression
	 * @param mixed[] $args
	 */
	public static function addCondition(Closure $addFn, $expression, ...$args) {
		if($expression instanceof OptionalExpression) {
			if($expression->isValid()) {
				$addFn($expression->getExpression(), $expression->getValue());
			}
		} elseif(is_object($expression)) {
			self::addAsArray($addFn, (array) $expression, $args);
		} elseif(is_array($expression)) {
			self::addAsArray($addFn, $expression, $args);
		} else {
			$addFn($expression, $args);
		}
	}

	/**
	 * @param Closure $addFn
	 * @param array $expression
	 * @param array $args
	 */
	private static function addAsArray(Closure $addFn, array $expression, array $args) {
		if(count($expression) > 0) {
			$addFn($expression, $args);
		}
	}
}
