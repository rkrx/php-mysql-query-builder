<?php
namespace Kir\MySQL;

use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Database\DatabaseStatement;
use Kir\MySQL\Tools\AliasRegistry;
use Kir\MySQL\Tools\VirtualTables;

interface Database {
	/**
	 * @return AliasRegistry
	 */
	public function getAliasRegistry(): AliasRegistry;

	/**
	 * @return VirtualTables
	 */
	public function getVirtualTables(): VirtualTables;

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
	 * @param array<string, mixed> $params
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
	 * @return array<int, string>
	 */
	public function getTableFields(string $table): array;

	/**
	 * @param string $expression
	 * @param array<int, null|int|float|string|array<int, string>|Builder\DBExpr|Builder\Select> $arguments
	 * @return string
	 */
	public function quoteExpression(string $expression, array $arguments = []): string;

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
	 * @param array<int|string, string>|null $fields
	 * @return Builder\Select
	 */
	public function select(?array $fields = null);

	/**
	 * @param array<string, string>|null $fields
	 * @return Builder\Insert
	 */
	public function insert(?array $fields = null);

	/**
	 * @param array<string, string>|null $fields
	 * @return Builder\Update
	 */
	public function update(?array $fields = null);

	/**
	 * @return Builder\Delete
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
	 * @template T
	 * @param callable(self): T $callback
	 * @return T
	 */
	public function transaction(callable $callback);

	/**
	 * @template T
	 * @param callable(self): T $callback
	 * @return T
	 */
	public function dryRun(callable $callback);
}
