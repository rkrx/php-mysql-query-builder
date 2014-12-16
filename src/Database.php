<?php
namespace Kir\MySQL;

use Kir\MySQL\Builder\Exception;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Tools\AliasRegistry;

interface Database {
	/**
	 * @return AliasRegistry
	 */
	public function getAliasRegistry();

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
	 * @param array $params
	 * @return int
	 */
	public function exec($query, array $params = array());

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
	 * @param int|callable $tries
	 * @param callable|null $callback
	 * @return $this
	 * @throws \Exception
	 */
	public function transaction($tries = 1, $callback = null);
}