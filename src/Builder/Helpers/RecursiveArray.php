<?php
namespace Kir\MySQL\Builder\Helpers;

class RecursiveArray {
	/**
	 * @param array $array
	 * @param array $path
	 * @param mixed $default
	 * @return array
	 */
	public static function get($array, $path, $default = null) {
		$count = count($path);
		if (!$count) {
			return $default;
		}
		for($idx = 0; $idx < $count; $idx++) {
			$part = $path[$idx];
			if(!array_key_exists($part, $array)) {
				return $default;
			}
			$array = $array[$part];
		}
		return $array;
	}
}
