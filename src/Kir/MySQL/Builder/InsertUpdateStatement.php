<?php
namespace Kir\MySQL\Builder;

use PDO;

abstract class InsertUpdateStatement extends Statement {
	/**
	 * @var array
	 */
	private static $tableFields = array();

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
	 * @param string $table
	 * @return array
	 */
	protected function getTableFields($table) {
		if(array_key_exists($table, self::$tableFields)) {
			return self::$tableFields[$table];
		}
		$stmt = $this->mysql()->query("DESCRIBE {$table}");
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		self::$tableFields[$table] = array_map(function ($row) { return $row['Field']; }, $rows);
		$stmt->closeCursor();
		return self::$tableFields[$table];
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
				$fieldName = $this->mysql()->quoteField($fieldName);
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