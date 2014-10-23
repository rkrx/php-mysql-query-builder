<?php
namespace Kir\MySQL\Databases;

use Kir\MySQL\Builder;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Database;

class Mock implements Database {
	/**
	 * @var Database
	 */
	private $db = null;

	/**
	 */
	function __construct() {
		$this->db = new MySQL(new \PDO('sqlite::memory:'));
	}

	/**
	 * @param string $query
	 * @return \PDOStatement
	 */
	public function query($query) {
		return null;
	}

	/**
	 * @param string $query
	 * @return \PDOStatement
	 */
	public function prepare($query) {
		return null;
	}

	/**
	 * @param string $query
	 * @return int
	 */
	public function exec($query) {
		return 1;
	}

	/**
	 * @return int
	 */
	public function getLastInsertId() {
		return 1;
	}

	/**
	 * @param string $table
	 * @return array
	 */
	public function getTableFields($table) {
		return array(
			'ean'
		);
	}

	/**
	 * @param mixed $expression
	 * @param array $arguments
	 * @return string
	 */
	public function quoteExpression($expression, array $arguments = array()) {
		return $this->db->quoteExpression($expression, $arguments);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function quote($value) {
		return $this->db->quote($value);
	}

	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteField($field) {
		return $this->db->quoteField($field);
	}

	/**
	 * @param array $fields
	 * @return RunnableSelect
	 */
	public function select(array $fields = array()) {
		return null;
	}

	/**
	 * @return Builder\RunnableInsert
	 */
	public function insert() {
		return null;
	}

	/**
	 * @return Builder\RunnableUpdate
	 */
	public function update() {
		return null;
	}

	/**
	 * @return Builder\RunnableDelete
	 */
	public function delete() {
		return null;
	}

	/**
	 * @return $this
	 */
	public function transactionStart() {
	}

	/**
	 * @return $this
	 */
	public function transactionCommit() {
	}

	/**
	 * @return $this
	 */
	public function transactionRollback() {
	}

	/**
	 * @param callable $callback
	 * @throws \Exception
	 * @return $this
	 */
	public function transaction($callback) {
	}
}