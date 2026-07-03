<?php

namespace Kir\MySQL\Builder\Internal;

use Kir\MySQL\Builder\Expr\ConditionExpression;
use Kir\MySQL\Builder\Expr\OptionalExpression;
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
		$arr = [];
		foreach($conditions as [$expression, $arguments]) {
			foreach(self::buildConditionParts($db, $expression, $arguments) as $condition) {
				$arr[] = "\t({$condition})";
			}
		}
		if(!count($arr)) {
			return $query;
		}

		$query .= "{$token}\n";
		$query .= implode("\n\tAND\n", $arr);

		return "{$query}\n";
	}

	/**
	 * @param Database $db
	 * @param DBWhereExpressionType $expression
	 * @param list<DBParameterValueType> $arguments
	 * @return string|null
	 */
	public static function buildExpression(Database $db, $expression, array $arguments = []): ?string {
		$parts = self::buildConditionParts($db, $expression, $arguments);
		if(count($parts) < 1) {
			return null;
		}
		if(count($parts) === 1) {
			return $parts[0];
		}

		return implode(' AND ', array_map(static fn(string $condition): string => "({$condition})", $parts));
	}

	/**
	 * @param Database $db
	 * @param DBWhereExpressionType $expression
	 * @param list<DBParameterValueType> $arguments
	 * @return string[]
	 */
	private static function buildConditionParts(Database $db, $expression, array $arguments): array {
		if($expression instanceof ConditionExpression) {
			$condition = $expression->buildCondition($db);
			return $condition === null ? [] : [$condition];
		}
		if($expression instanceof OptionalExpression) {
			if(!$expression->isValid()) {
				return [];
			}

			return self::buildConditionParts($db, $expression->getExpression(), [$expression->getValue()]);
		}
		if(is_object($expression) && !$expression instanceof Stringable) {
			$expression = (array) $expression;
		}
		if(is_array($expression)) {
			$parts = [];
			foreach($expression as $key => $value) {
				$key = self::formatKey((string) $key);
				if($value === null) {
					$parts[] = self::buildCondition("ISNULL({$key})", [$value], $db);
				} else {
					$parts[] = self::buildCondition("{$key}=?", [$value], $db);
				}
			}

			return $parts;
		}

		/** @var Stringable|string $expression */
		return [self::buildCondition((string) $expression, $arguments, $db)];
	}

	/**
	 * @param string $expression
	 * @param list<DBParameterValueType> $arguments
	 * @param Database $db
	 * @return string
	 */
	private static function buildCondition(string $expression, array $arguments, Database $db): string {
		return $db->quoteExpression($expression, $arguments);
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
