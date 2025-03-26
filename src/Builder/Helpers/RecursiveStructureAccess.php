<?php

namespace Kir\MySQL\Builder\Helpers;

use ArrayObject;

class RecursiveStructureAccess {
	/**
	 * @param object|array<string, mixed> $structure
	 * @param string|string[] $path
	 * @return bool
	 */
	public static function recursiveHas($structure, $path): bool {
		$arrayPath = self::getArrayPath($path);
		return self::_recursiveHas($structure, $arrayPath);
	}

	/**
	 * @param object|array<string, mixed> $structure
	 * @param string|string[] $path
	 * @param mixed $default
	 * @return mixed
	 */
	public static function recursiveGet($structure, $path, $default) {
		$arrayPath = self::getArrayPath($path);
		$data = self::ensureArrayIsArray($structure);
		$count = count($arrayPath);
		if (!$count) {
			return $default;
		}
		foreach($arrayPath as $idxValue) {
			$part = $idxValue;
			if(!array_key_exists($part, $data)) {
				return $default;
			}
			$data = $data[$part];
		}
		return $data;
	}

	/**
	 * @param mixed $structure
	 * @param string[] $path
	 * @return bool
	 */
	private static function _recursiveHas($structure, array $path): bool {
		$data = self::ensureArrayIsArray($structure);
		if($data === null) {
			return false;
		}
		if(!count($path)) {
			return false;
		}
		$key = array_shift($path);
		if(count($path)) {
			return self::_recursiveHas($data[$key] ?? null, $path);
		}
		return array_key_exists($key, $data);
	}

	/**
	 * @param string|string[] $path
	 * @return string[]
	 */
	private static function getArrayPath($path): array {
		if(is_array($path)) {
			return $path;
		}
		$parts = preg_split('{(?<!\\x5C)(?:\\x5C\\x5C)*\\.}', $path);
		if($parts === false) {
			return [$path];
		}
		return $parts;
	}

	/**
	 * @param mixed $array
	 * @return array<mixed, mixed>
	 */
	private static function ensureArrayIsArray($array): ?array {
		if($array instanceof ArrayObject) {
			return $array->getArrayCopy();
		}
		if(is_object($array)) {
			return (array) $array;
		}
		if(is_array($array)) {
			return $array;
		}
		return null;
	}
}
