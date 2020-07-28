<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DefaultValue;

abstract class InsertUpdateStatement extends Statement {
	/** @var array */
	private $mask;

	/**
	 * @return array|null
	 */
	public function getMask() {
		return $this->mask;
	}

	/**
	 * @param array $mask
	 * @return $this
	 */
	public function setMask(array $mask) {
		$this->mask = $mask;
		return $this;
	}

	/**
	 * @param array $fields
	 * @param array $query
	 * @return string[]
	 */
	protected function buildFieldList(array $fields, array $query = []) {
		foreach ($fields as $fieldName => $fieldValue) {
			if ($fieldValue instanceof DefaultValue) {
				$fieldValue = 'DEFAULT';
			}
			if (is_array($this->mask) && !in_array($fieldName, $this->mask)) {
				continue;
			}
			if (is_int($fieldName)) {
				if (is_array($fieldValue)) {
					$fieldValue = $this->db()->quoteExpression($fieldValue[0], array_slice($fieldValue, 1));
				}
				$query[] = "\t{$fieldValue}";
			} else {
				$fieldName = $this->db()->quoteField($fieldName);
				$query[] = "\t{$fieldName}={$fieldValue}";
			}
		}
		return $query;
	}

	/**
	 * @param string $fieldName
	 * @param array $tableFields
	 * @return bool
	 */
	protected function isFieldAccessible($fieldName, array $tableFields) {
		if (!in_array($fieldName, $tableFields)) {
			return false;
		}
		if (!is_array($this->mask)) {
			return true;
		}
		return in_array($fieldName, $this->mask);
	}
}
