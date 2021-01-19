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
	public static function build(Database $db, string $query, array $conditions, string $token): string {
		if(!count($conditions)) {
			return $query;
		}
		$query .= "{$token}\n";
		$arr = [];
		foreach($conditions as list($expression, $arguments)) {
			if(is_array($expression)) {
				foreach($expression as $key => $value) {
					if($value === null) {
						$arr = self::buildCondition($arr, "ISNULL(`{$key}`)", [$value], $db);
					} else {
						$arr = self::buildCondition($arr, "`{$key}`=?", [$value], $db);
					}
				}
			} else {
				$arr = self::buildCondition($arr, $expression, $arguments, $db);
			}
		}
		$query .= implode("\n\tAND\n", $arr);
		return "{$query}\n";
	}

	/**
	 * @param array $conditions
	 * @param mixed $expression
	 * @param mixed $arguments
	 * @param Database $db
	 * @return array
	 */
	private static function buildCondition(array $conditions, $expression, $arguments, Database $db): array {
		$expr = $db->quoteExpression($expression, $arguments);
		$conditions[] = "\t({$expr})";
		return $conditions;
	}
}
