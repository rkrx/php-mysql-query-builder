<?php
namespace Kir\MySQL\Databases\MySQL;

use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\Select;
use PDO;
use phpDocumentor\Reflection\Types\Scalar;

class MySQLValueQuoter {
	/**
	 * @param PDO $pdo
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select $value
	 * @return string
	 */
	public static function quote(PDO $pdo, $value): string {
		if(is_null($value)) {
			return 'NULL';
		}

		if(is_bool($value)) {
			return $value ? '1' : '0';
		}

		if(is_array($value)) {
			$fn = static function ($value) use ($pdo) {
				return self::quote($pdo, $value);
			};
			return implode(', ', array_map($fn, $value));
		}

		if($value instanceof DBExpr) {
			return $value->getExpression();
		}

		if($value instanceof Select) {
			return sprintf('(%s)', (string) $value);
		}

		if(is_int($value) || is_float($value)) {
			return (string) $value;
		}

		return $pdo->quote($value);
	}
}
