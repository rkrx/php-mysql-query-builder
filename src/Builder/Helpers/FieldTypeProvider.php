<?php

namespace Kir\MySQL\Builder\Helpers;

use Kir\MySQL\Builder\QueryStatement;

abstract class FieldTypeProvider {
	/**
	 * @param QueryStatement $statement
	 * @return array<string, string>
	 */
	public static function getFieldTypes(QueryStatement $statement): array {
		$fieldTypes = [];
		for($i = 0; $column = $statement->getColumnMeta($i); $i++) {
			$name = $column['name'] ?? null;
			$nativeType = $column['native_type'] ?? null;
			if(is_string($name) && is_string($nativeType)) {
				$fieldTypes[$name] = self::getTypeFromNativeType($nativeType);
			}
		}

		return $fieldTypes;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	private static function getTypeFromNativeType(string $type): string {
		return match ($type) {
			'NEWDECIMAL', 'DECIMAL', 'FLOAT', 'DOUBLE' => 'f',
			'TINY', 'SHORT', 'LONG', 'LONGLONG', 'INT24' => 'i',
			default => $type,
		};
	}
}
