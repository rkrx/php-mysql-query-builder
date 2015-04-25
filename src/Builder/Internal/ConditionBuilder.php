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
		$arr = array();
		foreach($conditions as $condition) {
			list($expression, $arguments) = $condition;
			$expr = $db->quoteExpression($expression, $arguments);
			$arr[] = "\t({$expr})";
		}
		$query .= join("\n\tAND\n", $arr);
		return $query."\n";
	}
}