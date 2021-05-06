<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DefaultValue;
use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\Select;

abstract class InsertUpdateStatement extends Statement {
	/** @var array<int, string> */
	private $mask;

	/**
	 * @return array<int, string>|null
	 */
	public function getMask(): ?array {
		return $this->mask;
	}

	/**
	 * @param array<int, string> $mask
	 * @return $this
	 */
	public function setMask(array $mask) {
		$this->mask = $mask;
		return $this;
	}

	/**
	 * @param array<int|string, null|string|array<int, string>|DBExpr|Select|DefaultValue> $fields
	 * @param array<int, string> $query
	 * @return string[]
	 */
	protected function buildFieldList(array $fields, array $query = []): array {
		foreach ($fields as $fieldName => $fieldValue) {
			if($fieldValue instanceof DefaultValue) {
				$fieldValue = 'DEFAULT';
			}
			if(is_array($this->mask) && !in_array($fieldName, $this->mask, true)) {
				continue;
			}
			if(is_int($fieldName)) {
				if (is_array($fieldValue)) {
					$fieldValue = $this->db()->quoteExpression($fieldValue[0], array_slice($fieldValue, 1));
				}
				$query[] = "\t{$fieldValue}";
			} else {
				$fieldName = $this->db()->quoteField($fieldName);
				if (is_array($fieldValue)) {
					$fieldValue = $this->db()->quoteExpression($fieldValue[0], array_slice($fieldValue, 1));
				}
				$query[] = "\t{$fieldName}={$fieldValue}";
			}
		}
		return $query;
	}

	/**
	 * @param string $fieldName
	 * @param array<int, string> $tableFields
	 * @return bool
	 */
	protected function isFieldAccessible(string $fieldName, array $tableFields): bool {
		if (!in_array($fieldName, $tableFields)) {
			return false;
		}
		if (!is_array($this->mask)) {
			return true;
		}
		return in_array($fieldName, $this->mask, true);
	}
}
