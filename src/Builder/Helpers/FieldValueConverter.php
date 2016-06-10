<?php
namespace Kir\MySQL\Builder\Helpers;

class FieldValueConverter {
	/**
	 * @param array $row
	 * @param array $columnDefinitions
	 * @return array
	 */
	public static function convertValues(array $row, array $columnDefinitions) {
		foreach($row as $key => &$value) {
			if($value !== null) {
				$value = self::convertValue($value, $columnDefinitions[$key]);
			}
		}
		return $row;
	}

	/**
	 * @param mixed $value
	 * @param string $type
	 * @return mixed
	 */
	private static function convertValue($value, $type) {
		switch ($type) {
			case 'i':
				return $value !== null ? (int) $value : null;
			case 'f':
				return $value !== null ? (float) $value : null;
		}
		return $value;
	}
}
