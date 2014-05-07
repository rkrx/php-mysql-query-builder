<?php
namespace Kir\MySQL\Builder;

abstract class InsertUpdateStatement extends Statement {
	/**
	 * @var array
	 */
	private $mask = null;

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
	 * @param array $tableFields
	 * @param array $result
	 * @return string
	 */
	protected function buildFieldList(array $fields, array $tableFields, array $result = array()) {
		foreach ($fields as $fieldName => $fieldValue) {
			if (is_int($fieldName)) {
				$result[] = $fieldValue;
			} elseif ($this->isFieldAccessible($fieldName, $tableFields)) {
				$fieldName = $this->db()->quoteField($fieldName);
				$result[] = "\t{$fieldName} = {$fieldValue}";
			}
		}
		return join(",\n", $result);
	}

	/**
	 * @param string $fieldName
	 * @param array $tableFields
	 * @return bool
	 */
	protected function isFieldAccessible($fieldName, array $tableFields) {
		if(!in_array($fieldName, $tableFields)) {
			return false;
		}
		if(!is_array($this->mask)) {
			return true;
		}
		return !in_array($fieldName, $this->mask);
	}
} 