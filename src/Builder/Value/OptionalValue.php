<?php

namespace Kir\MySQL\Builder\Value;

interface OptionalValue {
	/**
	 * @return bool If true, the Value is present
	 */
	public function isValid(): bool;

	/**
	 * @return mixed
	 */
	public function getValue();
}
