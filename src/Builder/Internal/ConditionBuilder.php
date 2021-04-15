<?php
namespace Kir\MySQL\Builder\Internal;

use Kir\MySQL\Database;
use Kir\MySQL\Builder;

final class ConditionBuilder {
	/**
	 * @param Database $db
	 * @param string $query
	 * @param array<int, array{string|array<string, mixed>|object|OptionalExpression, array<int, null|string|array<int, string>|Builder\DBExpr|Builder\Select>}> $conditions
	 * @param string $token
	 * @return string
	 */
	public static function build(Database $db, string $query, array $conditions, string $token): string {
		if(!count($conditions)) {
			return $query;
		}
		$query .= "{$token}\n";
		$arr = [];
		foreach($conditions as [$expression, $arguments]) {
			if(is_array($expression)) {
				foreach($expression as $key => $value) {
					if($value === null) {
						$arr = self::buildCondition($arr, "ISNULL(`{$key}`)", [$value], $db);
					} else {
						$arr = self::buildCondition($arr, "`{$key}`=?", [$value], $db);
					}
				}
			} else {
				$arr = self::buildCondition($arr, (string) $expression, $arguments, $db);
			}
		}
		$query .= implode("\n\tAND\n", $arr);
		return "{$query}\n";
	}

	/**
	 * @param array<int, string> $conditions
	 * @param string $expression
	 * @param array<int, null|string|array<int, string>|Builder\DBExpr|Builder\Select> $arguments
	 * @param Database $db
	 * @return array<int, string>
	 */
	private static function buildCondition(array $conditions, string $expression, array $arguments, Database $db): array {
		$expr = $db->quoteExpression($expression, $arguments);
		$conditions[] = "\t({$expr})";
		return $conditions;
	}
}
