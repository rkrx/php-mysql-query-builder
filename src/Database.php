<?php
namespace Kir\MySQL;

use Kir\MySQL\Builder\Exception;
use Kir\MySQL\Database\DatabaseStatement;
use Kir\MySQL\QueryLogger\QueryLoggers;
use Kir\MySQL\Tools\AliasRegistry;
use Kir\MySQL\Tools\ExtensionMethodRegistry;

interface Database {
	/**
	 * @return QueryLoggers
	 */
	public function getQueryLoggers();

	/**
	 * @return AliasRegistry
	 */
	public function getAliasRegistry();

	/**
	 * @return ExtensionMethodRegistry
	 */
	public function getExtensionMethodRegistry();

	/**
	 * @param string $query
	 * @throws Exception
	 * @return DatabaseStatement
	 */
	public function query($query);

	/**
	 * @param string $query
	 * @throws Exception
	 * @return DatabaseStatement
	 */
	public function prepare($query);

	/**
	 * @param string $query
	 * @param array $params
	 * @return int
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
	 * @return mixed
	 * @throws \Exception
	 */
	public function transaction($tries = 1, $callback = null);
}
