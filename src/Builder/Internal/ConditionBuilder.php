<?php
namespace Kir\MySQL\Builder\Internal;

use Kir\MySQL\Database;
use Stringable;

/**
 * @phpstan-import-type DBParameterValueType from Types
 * @phpstan-import-type DBWhereExpressionType from Types
 */
final class ConditionBuilder {
	/**
	 * @param Database $db
	 * @param string $query
	 * @param array<int, array{DBWhereExpressionType, list<DBParameterValueType>}> $conditions
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
					$key = self::formatKey($key);
					if($value === null) {
						$arr = self::buildCondition($arr, "ISNULL({$key})", [$value], $db);
					} else {
						$arr = self::buildCondition($arr, "{$key}=?", [$value], $db);
					}
				}
			} else {
				/** @var Stringable|string $expression */
				$arr = self::buildCondition($arr, (string) $expression, $arguments, $db);
			}
		}
		$query .= implode("\n\tAND\n", $arr);
		return "{$query}\n";
	}

	/**
	 * @param string[] $conditions
	 * @param string $expression
	 * @param list<DBParameterValueType> $arguments
	 * @param Database $db
	 * @return string[]
	 */
	private static function buildCondition(array $conditions, string $expression, array $arguments, Database $db): array {
		$expr = $db->quoteExpression($expression, $arguments);
		$conditions[] = "\t({$expr})";
		return $conditions;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	private static function formatKey(string $key): string {
		if(strpos($key, '`') !== false || strpos($key, '(') !== false) {
			return $key;
		}
		$keyParts = explode('.', $key);
		$fn = static fn(string $part) => "`{$part}`";
		$enclosedKeyParts = array_map($fn, $keyParts);
		return implode('.', $enclosedKeyParts);
	}
}
