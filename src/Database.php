<?php
namespace Kir\MySQL;

use Kir\MySQL\Database\DatabaseStatement;

interface Database {
	/**
	 * @param string $query
	 * @return DatabaseStatement
	 */
	public function query($query);

	/**
	 * @param string $query
	 * @return DatabaseStatement
	 */
	public function prepare($query);

	/**
	 * @param string $query
	 * @param array $params
	 * @return int
	 */
	public function exec($query, array $params = []);

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
	public function quoteExpression($expression, array $arguments = []);

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
	 * @param callable|null $callback
	 * @return mixed
	 */
	public function transaction(callable $callback = null);

	/**
	 * @param callable|null $callback
	 * @return mixed
	 */
	public function dryRun($callback = null);
}
