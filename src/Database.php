<?php
namespace Kir\MySQL;

use Kir\MySQL\Database\DatabaseStatement;
use RuntimeException;

interface Database {
	/**
	 * @param string $query
	 * @return DatabaseStatement
	 * @throws RuntimeException
	 */
	public function query($query);

	/**
	 * @param string $query
	 * @return DatabaseStatement
	 * @throws RuntimeException
	 */
	public function prepare($query);

	/**
	 * @param string $query
	 * @param array $params
	 * @return int
	 * @throws RuntimeException
	 */
	public function exec($query, array $params = array());

	/**
	 * @return string
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
	 * @return Builder\RunnableSelect
	 */
	public function select(array $fields = null);

	/**
	 * @param array $fields
	 * @return Builder\RunnableInsert
	 */
	public function insert(array $fields = null);

	/**
	 * @param array $fields
	 * @return Builder\RunnableUpdate
	 */
	public function update(array $fields = null);

	/**
	 * @return Builder\RunnableDelete
	 */
	public function delete();

	/**
	 * @return $this
	 * @throws RuntimeException
	 */
	public function transactionStart();

	/**
	 * @return $this
	 * @throws RuntimeException
	 */
	public function transactionCommit();

	/**
	 * @return $this
	 * @throws RuntimeException
	 */
	public function transactionRollback();

	/**
	 * @param int|callable $tries
	 * @param callable|null $callback
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function transaction($tries = 1, $callback = null);

	/**
	 * @param callable|null $callback
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function dryRun($callback = null);
}
