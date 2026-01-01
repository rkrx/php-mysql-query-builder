<?php

namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\InvalidValueException;
use Kir\MySQL\Builder\Value\OptionalValue;

trait LimitBuilder {
	private null|int|OptionalValue $limit = null;

	/**
	 * @return null|int
	 */
	protected function getLimit(): ?int {
		if($this->limit instanceof OptionalValue) {
			$value = $this->limit->getValue();
			if(is_numeric($value)) {
				return (int) $value;
			}

			return null;
		}

		return $this->limit;
	}

	/**
	 * @param null|int|OptionalValue $limit
	 * @return $this
	 */
	public function limit($limit) {
		$this->limit = $limit;

		return $this;
	}

	/**
	 * @param string $query
	 * @param int|null $offset
	 * @return string
	 */
	protected function buildLimit(string $query, ?int $offset = null) {
		$limit = $this->getLimit();
		if($limit === null && $offset !== null) {
			$limit = '18446744073709551615';
		}
		if($this->limit instanceof OptionalValue) {
			if($this->limit->isValid()) {
				$value = $this->limit->getValue();
				if($value === null || is_scalar($value)) {
					$value = (string) $value;
				} else {
					throw new InvalidValueException('Value for OFFSET has to be a number');
				}
				if(!preg_match('{^\\d+$}', $value)) {
					throw new InvalidValueException('Value for OFFSET has to be a number');
				}
				$query .= "LIMIT\n\t{$value}\n";
			}
		} elseif($limit !== null) {
			$query .= "LIMIT\n\t{$limit}\n";
		}

		return $query;
	}
}
