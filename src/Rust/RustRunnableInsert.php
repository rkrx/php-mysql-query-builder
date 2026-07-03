<?php

namespace Kir\MySQL\Rust;

use Kir\MySQL\Builder\RunnableInsert;
use Kir\MySQL\Builder\Select;
use Kir\MySQL\Databases\MySQL;
use Traversable;
use UnexpectedValueException;

class RustRunnableInsert extends RunnableInsert {
	use NativeStatementSupport;

	/** @var object */
	private $native;
	private ?string $table = null;
	private ?string $keyField = null;

	/**
	 * @param MySQL $db
	 * @param array<string, mixed> $options
	 */
	public function __construct($db, array $options = []) {
		parent::__construct($db, $options);
		$this->native = $this->createNativeBuilder('Insert');
	}

	public function into(string $table) {
		$this->table = $table;
		$this->native->setTable($this->aliasReplacer()->replace($table));

		return $this;
	}

	public function setIgnore(bool $value = true) {
		$this->native->setIgnore($value);

		return $this;
	}

	public function setKey(string $field) {
		$this->keyField = $field;
		$this->native->setKeyField($field);

		return $this;
	}

	public function setMask(array $mask) {
		parent::setMask($mask);
		$this->native->setMask(array_values($mask));

		return $this;
	}

	public function add(string $field, $value) {
		if(!$this->isNativeFieldNameValid($field)) {
			throw new UnexpectedValueException('Field name is invalid');
		}
		$this->native->addField($field, $this->db()->quote($value));

		return $this;
	}

	public function update(string $field, $value) {
		if(!$this->isNativeFieldNameValid($field)) {
			throw new UnexpectedValueException('Field name is invalid');
		}
		$this->native->addUpdateField($field, $this->db()->quote($value));

		return $this;
	}

	public function addOrUpdate(string $field, $value) {
		$this->add($field, $value);
		$this->update($field, $value);

		return $this;
	}

	public function addExpr(string $str, ...$args) {
		$this->native->addRawField(count($args) > 0 ? $this->db()->quoteExpression($str, $args) : $str);

		return $this;
	}

	public function updateExpr(string $str, ...$args) {
		$this->native->addRawUpdateField(count($args) > 0 ? $this->db()->quoteExpression($str, $args) : $str);

		return $this;
	}

	public function addOrUpdateExpr(string $expr, ...$args) {
		$sql = count($args) > 0 ? $this->db()->quoteExpression($expr, $args) : $expr;
		$this->native->addRawField($sql);
		$this->native->addRawUpdateField($sql);

		return $this;
	}

	public function addAll(array $data, ?array $mask = null, ?array $excludeFields = null) {
		foreach($this->filterNativeInsertValues($data, $mask, $excludeFields) as $field => $value) {
			$this->add($field, $value);
		}

		return $this;
	}

	public function updateAll(array $data, ?array $mask = null, ?array $excludeFields = null) {
		foreach($this->filterNativeInsertValues($data, $mask, $excludeFields) as $field => $value) {
			if($field !== $this->keyField) {
				$this->update($field, $value);
			}
		}

		return $this;
	}

	public function addOrUpdateAll(array $data, ?array $mask = null, ?array $excludeFields = null) {
		$this->addAll($data, $mask, $excludeFields);
		$this->updateAll($data, $mask, $excludeFields);

		return $this;
	}

	public function from(Select $select) {
		$this->native->setFromSelect(array_map('strval', array_keys($select->getFields())), (string) $select);

		return $this;
	}

	/**
	 * @param iterable<int, array<string, mixed>>|Traversable<int, array<string, mixed>> $rows
	 * @return int[]
	 */
	public function insertRows(iterable $rows) {
		return parent::insertRows($rows);
	}

	private function isNativeFieldNameValid(string $fieldName): bool {
		return !(is_numeric($fieldName) || !is_scalar($fieldName));
	}

	/**
	 * @param array<string, mixed> $data
	 * @param string[]|null $mask
	 * @param string[]|null $excludeFields
	 * @return array<string, mixed>
	 */
	private function filterNativeInsertValues(array $data, ?array $mask, ?array $excludeFields): array {
		if($mask !== null) {
			$data = array_intersect_key($data, array_combine($mask, $mask));
		}
		if($excludeFields !== null) {
			foreach($excludeFields as $excludeField) {
				unset($data[$excludeField]);
			}
		}
		if(!count($data) || $this->table === null) {
			return [];
		}

		$tableName = $this->aliasReplacer()->replace($this->table);
		$fields = $this->db()->getTableFields($tableName);
		$result = [];
		foreach($data as $fieldName => $fieldValue) {
			if(in_array($fieldName, $fields, true)) {
				$result[$fieldName] = $fieldValue;
			}
		}

		return $result;
	}

	public function __toString(): string {
		return $this->native->toString();
	}
}
