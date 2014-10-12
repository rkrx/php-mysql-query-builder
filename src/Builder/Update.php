<?php
namespace Kir\MySQL\Builder;

use Exception;
use UnexpectedValueException;

class Update extends InsertUpdateStatement {
	/**
	 * @var array
	 */
	private $fields = array();

	/**
	 * @var string
	 */
	private $table = null;

	/**
	 * @var array
	 */
	private $where = array();

	/**
	 * @var int
	 */
	private $limit = null;

	/**
	 * @param string $name
	 * @return $this
	 */
	public function table($name) {
		$this->table = $name;
		return $this;
	}

	/**
	 * @param string $field
	 * @param string $value
	 * @throws UnexpectedValueException
	 * @return $this
	 */
	public function set($field, $value) {
		$sqlField = $field;
		$sqlValue = $this->db()->quote($value);
		$this->fields[$sqlField] = $sqlValue;
		return $this;
	}

	/**
	 * @param string $expr
	 * @return $this
	 */
	public function setExpr($expr) {
		$this->fields[] = $expr;
		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	public function setAll(array $data) {
		foreach ($data as $field => $value) {
			$this->set($field, $value);
		}
		return $this;
	}

	/**
	 * @param string $expr
	 * @return $this
	 */
	public function where($expr) {
		$expr = $this->db()->quoteExpression($expr, array_slice(func_get_args(), 1));
		$this->where[] = "({$expr})";
		return $this;
	}

	/**
	 * @param int $count
	 * @return self
	 */
	public function limit($count) {
		$this->limit = $count;
		return $this;
	}

	/**
	 * @throws Exception
	 * @return string
	 */
	public function __toString() {
		if ($this->table === null) {
			throw new Exception('Specify a table-name');
		}

		$tableFields = $this->db()->getTableFields($this->table);
		$sqlFields = $this->buildFieldList($this->fields, $tableFields);

		if (empty($sqlFields)) {
			throw new Exception('No field-data found');
		}

		$queryArr = array();
		$queryArr[] = "UPDATE\n\t{$this->table}\nSET\n{$sqlFields}\n";

		if (!empty($this->where)) {
			$sqlWhere = join("\n\tAND\n\t", $this->where);
			$queryArr[] = "WHERE\n\t{$sqlWhere}\n";
		}

		if($this->limit !== null) {
			$queryArr[] = "LIMIT\n\t{$this->limit}\n";
		}

		$queryArr[] = ";\n";

		return join('', $queryArr);
	}
}