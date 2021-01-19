<?php
namespace Kir\MySQL;

use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Database\DatabaseStatement;

interface Database {
	/**
	 * @param string $query
	 * @return DatabaseStatement
	 */
	public function query(string $query);

	/**
	 * @param string $query
	 * @return QueryStatement
	 */
	public function prepare(string $query);

	/**
	 * @param string $query
	 * @param array $params
	 * @return int
	 */
	public function exec(string $query, array $params = []): int;

	/**
	 * @param string|null $name
	 * @return string
	 */
	public function getLastInsertId(?string $name = null): string;

	/**
	 * @param string $table
	 * @return array
	 */
	public function getTableFields(string $table): array;

	/**
	 * @param mixed $expression
	 * @param array $arguments
	 * @return string
	 */
	public function quoteExpression($expression, array $arguments = []): string;

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function quote($value): string;

	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteField(string $field): string;

	/**
	 * @param array|null $fields
	 * @return Builder\RunnableSelect
	 */
	public function select(array $fields = null): Builder\RunnableSelect;

	/**
	 * @param array|null $fields
	 * @return Builder\RunnableInsert
	 */
	public function insert(array $fields = null): Builder\RunnableInsert;

	/**
	 * @param array|null $fields
	 * @return Builder\RunnableUpdate
	 */
	public function update(array $fields = null): Builder\RunnableUpdate;

	/**
	 * @return Builder\RunnableDelete
	 */
	public function delete(): Builder\RunnableDelete;

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
