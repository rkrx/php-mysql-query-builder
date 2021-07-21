<?php

namespace Kir\MySQL\Builder\Value;

use Kir\MySQL\Builder\Helpers\RecursiveStructureAccess;

class DBOptionalValue implements OptionalValue {
	private $data;
	/** @var string|string[] */
	private $path;
	/** @var callable|null */
	private $validator;

	/**
	 * @param object|array<string, mixed>
	 * @param string|string[] $path
	 * @param null|callable(): bool $validator
	 */
	public function __construct($data, $path, $validator = null) {
		$this->data = $data;
		$this->path = $path;
		$this->validator = $validator;
	}

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
