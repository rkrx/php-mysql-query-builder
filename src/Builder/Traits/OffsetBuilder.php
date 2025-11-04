<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\InvalidValueException;
use Kir\MySQL\Builder\Value\OptionalValue;

trait OffsetBuilder {
	private null|int|OptionalValue $offset = null;

	/**
	 * @return null|int
	 */
	protected function getOffset(): ?int {
		if($this->offset instanceof OptionalValue) {
			$value = $this->offset->getValue();
			if(is_numeric($value)) {
				return (int) $value;
			}
			return null;
		}
		return $this->offset;
	}

	/**
	 * @param null|int|OptionalValue $offset
	 * @return $this
	 */
	public function offset($offset = 0) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildOffset(string $query): string {
		$offset = $this->getOffset();
		if($this->offset instanceof OptionalValue) {
			if($this->offset->isValid()) {
				$value = $this->offset->getValue();
				if($value === null || is_scalar($value)) {
					$value = (string) $value;
				} else {
					throw new InvalidValueException('Value for OFFSET has to be a number');
				}
				if(!preg_match('{^\\d+$}', $value)) {
					throw new InvalidValueException('Value for OFFSET has to be a number');
				}
				$query .= "OFFSET\n\t{$value}\n";
			}
		} elseif($offset !== null) {
			$query .= "OFFSET\n\t{$this->offset}\n";
		}
		return $query;
	}
}
