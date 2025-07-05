<?php

namespace Kir\MySQL\Builder\Value;

use Kir\MySQL\Builder\Helpers\RecursiveStructureAccess;

class DBOptionalValue implements OptionalValue {
	/**
	 * @param object|array<string, mixed> $data
	 * @param string|string[] $path
	 * @param null|callable(): bool $validator
	 */
	public function __construct(
		private object|array $data,
		private string|array $path,
		private $validator = null
	) {}

	/**
	 * @return bool
	 */
	public function isValid(): bool {
		if(!RecursiveStructureAccess::recursiveHas($this->data, $this->path)) {
			return false;
		}
		if($this->validator !== null) {
			$value = $this->getValue();
			return call_user_func($this->validator, $value);
		}
		return true;
	}

	/**
	 * @return mixed|null
	 */
	public function getValue() {
		return RecursiveStructureAccess::recursiveGet($this->data, $this->path, null);
	}
}
