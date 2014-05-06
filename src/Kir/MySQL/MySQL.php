<?php
namespace Kir\MySQL;

use PDO;
use PDOStatement;
use Kir\MySQL\Builder\RunnableSelect;
use UnexpectedValueException;

class MySQL {
	/**
	 * @var PDO
	 */
	private $pdo;

	/**
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * @param string $query
	 * @return PDOStatement
	 */
	public function query($query) {
		$stmt = $this->pdo->query($query);
		$stmt->execute();
		return $stmt;
	}

	/**
	 * @param string $query
	 * @return int
	 */
	public function exec($query) {
		return $this->pdo->exec($query);
	}

	/**
	 * @return int
	 */
	public function getLastInsertId() {
		return $this->pdo->lastInsertId();
	}

	/**
	 * @param mixed $expression
	 * @param array $arguments
	 * @return string
	 */
	public function quoteExpression($expression, array $arguments = array()) {
		$func = function ($oldValue) use ($arguments) {
			static $idx = -1;
			$idx++;
			$index = $idx;

			if(substr($oldValue[0], 0, 1) == ':') {
				$index = (int) substr($oldValue[0], 1);
			}

			if(array_key_exists($index, $arguments)) {
				$argument = $arguments[$index];
				$value = $this->quote($argument);
			} else {
				$value = 'NULL';
			}
			return $value;
		};
		return preg_replace_callback('/(\\?|:\\d+)/', $func, $expression);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function quote($value) {
		if(is_null($value)) {
			$result = 'NULL';
		} elseif(is_array($value)) {
			$result = join(', ', array_map(function ($value) { return $this->quote($value); }, $value));
		} elseif(is_numeric($value)) {
			$result = $value;
		} else {
			$result = $this->pdo->quote($value);
		}
		return $result;
	}

	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteField($field) {
		if (is_numeric($field) || !is_scalar($field)) {
			throw new UnexpectedValueException('Field name is invalid');
		}
		return "`{$field}`";
	}

	/**
	 * @param array $fields
	 * @return RunnableSelect
	 */
	public function select(array $fields = array()) {
		$select = new RunnableSelect($this);
		$select->fields($fields);
		return $select;
	}

	/**
	 * @return Builder\RunnableInsert
	 */
	public function insert() {
		return new Builder\RunnableInsert($this);
	}

	/**
	 * @return Builder\RunnableUpdate
	 */
	public function update() {
		return new Builder\RunnableUpdate($this);
	}

	/**
	 * @return Builder\RunnableDelete
	 */
	public function delete() {
		return new Builder\RunnableDelete($this);
	}
}