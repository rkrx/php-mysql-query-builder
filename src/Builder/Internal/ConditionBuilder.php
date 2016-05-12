<?php
namespace Kir\MySQL\Builder\Internal;

use Kir\MySQL\Database;

final class ConditionBuilder {
	/**
	 * @param Database $db
	 * @param string $query
	 * @param array $conditions
	 * @param string $token
	 * @return string
	 */
	public static function build(Database $db, $query, array $conditions, $token) {
		if(!count($conditions)) {
			return $query;
		}
		$query .= "{$token}\n";
		$arr = [];
		foreach($conditions as $condition) {
			list($expression, $arguments) = $condition;
			if(is_array($expression)) {
				foreach($expression as $key => $value) {
					$arr = self::buildCondition($arr, "`{$key}`=?", [$value], $db);
				}
			} else {
				$arr = self::buildCondition($arr, $expression, $arguments, $db);
			}
		}
		$query .= join("\n\tAND\n", $arr);
		return "{$query}\n";
	}

	/**
	 * @param array $conditions
	 * @param mixed $expression
	 * @param mixed $arguments
	 * @param Database $db
	 * @return array
	 */
	private static function buildCondition(array $conditions, $expression, $arguments, Database $db) {
		$expr = $db->quoteExpression($expression, $arguments);
		$conditions[] = "\t({$expr})";
		return $conditions;
	}
}
