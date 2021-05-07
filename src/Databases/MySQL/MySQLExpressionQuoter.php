<?php

namespace Kir\MySQL\Databases\MySQL;

use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\Select;
use PDO;

class MySQLExpressionQuoter {
	/**
	 * @param PDO $pdo
	 * @param string $expression
	 * @param array<int, null|scalar|array<int, string>|DBExpr|Select> $arguments
	 * @return string
	 */
	public static function quoteExpression(PDO $pdo, string $expression, array $arguments = []): string {
		$index = -1;
		$func = static function () use ($pdo, $arguments, &$index) {
			$index++;
			if(array_key_exists($index, $arguments)) {
				$argument = $arguments[$index];
				$value = MySQLValueQuoter::quote($pdo, $argument);
			} elseif(count($arguments) > 0) {
				$args = $arguments;
				$value = array_pop($args);
				$value = MySQLValueQuoter::quote($pdo, $value);
			} else {
				$value = 'NULL';
			}
			return $value;
		};
		return (string) preg_replace_callback('/(\\?)/', $func, $expression);
	}
}
