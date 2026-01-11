<?php

namespace Kir\MySQL\Builder\Helpers;

class FieldValueConverter {
	/**
	 * @param array<string, mixed> $row
	 * @param array<string, string> $columnDefinitions
	 * @return array<string, mixed>
	 */
	public static function convertValues(array $row, array $columnDefinitions): array {
		$result = [];
		foreach($row as $key => $value) {
			if($value !== null) {
				$result[$key] = self::convertValue($value, $columnDefinitions[$key]);
			} else {
				$result[$key] = $value;
			}
		}

		return $result;
	}

	/**
	 * @param mixed $value
	 * @param string $type
	 * @return mixed
	 */
	private static function convertValue($value, string $type) {
		return match ($type) {
			'i' => $value !== null ? (int) $value : null,
			'f' => $value !== null ? (float) $value : null,
			default => $value,
		};
	}
}
