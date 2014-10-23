<?php
namespace Kir\MySQL;

use Kir\MySQL\Builder\Exception;
use Kir\MySQL\Builder\RunnableSelect;

interface Database {
	/**
	 * @param string $query
	 * @throws Exception
	 * @return \PDOStatement
	 */
	public function query($query);

	/**
	 * @param string $query
	 * @throws Exception
	 * @return \PDOStatement
	 */
	public function prepare($query);

	/**
	 * @param string $query
	 * @return int
	 */
	public function exec($query);

	/**
	 * @return int
	 */
	public function getLastInsertId();

	/**
	 * @param string $table
	 * @return array
	 */
	public function getTableFields($table);

	/**
	 * @param mixed $expression
	 * @param array $arguments
	 * @return string
	 */
	public function quoteExpression($expression, array $arguments = array());

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function quote($value);

	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteField($field);

	/**
	 * @param array $fields
	 * @return RunnableSelect
	 */
	public function select(array $fields = array());

	/**
	 * @return Builder\RunnableInsert
	 */
	public function insert();

	/**
	 * @return Builder\RunnableUpdate
	 */
	public function update();

	/**
	 * @return Builder\RunnableDelete
	 */
	public function delete();

	/**
	 * @return $this
	 */
	public function transactionStart();

	/**
	 * @return $this
	 */
	public function transactionCommit();

	/**
	 * @return $this
	 */
	public function transactionRollback();

	/**
	 * @param callable $callback
	 * @throws \Exception
	 * @return $this
	 */
	public function transaction($callback);
}