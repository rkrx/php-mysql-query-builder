<?php
namespace Kir\MySQL\Databases\MySQL;

use UnexpectedValueException;

class MySQLFieldQuoter {
	/**
	 * @param string $field
	 * @return string
	 */
	public static function quoteField(string $field): string {
		if(is_numeric($field) || !is_string($field)) {
			throw new UnexpectedValueException('Field name is invalid');
		}
		if(strpos($field, '`') !== false) {
			return $field;
		}
		$parts = explode('.', $field);
		return '`'.implode('`.`', $parts).'`';
	}
}
