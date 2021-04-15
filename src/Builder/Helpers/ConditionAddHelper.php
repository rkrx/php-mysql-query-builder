<?php
namespace Kir\MySQL\Builder\Helpers;

use Kir\MySQL\Builder\Expr\OptionalExpression;

abstract class ConditionAddHelper {
	/**
	 * @param callable(string|array<string, mixed>, array<int, mixed>): void $addFn
	 * @param string|array<string, mixed>|object|OptionalExpression $expression
	 * @param array<int, mixed> $args
	 */
	public static function addCondition(callable $addFn, $expression, array $args): void {
		if($expression instanceof OptionalExpression) {
			if($expression->isValid()) {
				$addFn($expression->getExpression(), [$expression->getValue()]);
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
	 * @param callable(string|array<string, mixed>, array<int, mixed>): void $addFn
	 * @param array<string, mixed> $expression
	 * @param array<int, mixed> $args
	 */
	private static function addAsArray(callable $addFn, array $expression, array $args): void {
		if(count($expression) > 0) {
			$addFn($expression, $args);
		}
	}
}
