<?php

namespace Kir\MySQL\Databases\Mock;

use JetBrains\PhpStorm\Language;
use Kir\MySQL\Builder;
use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Database;
use Kir\MySQL\Tools\AliasRegistry;
use Kir\MySQL\Tools\VirtualTables;
use RuntimeException;

/**
 * Lightweight, in-memory implementation of {@see Database} intended for tests.
 * Results are taken from dedicated FIFO stacks (DQL/DML/DDL) that can be seeded
 * in the test setup via the addTo*Stack helper methods.
 */
class MockDatabase implements Database {
	private AliasRegistry $aliasRegistry;
	private ?VirtualTables $virtualTables = null;
	private ?string $lastInsertId = null;

	/** @var list<mixed> */
	private array $dqlStack = [];
	/** @var list<mixed> */
	private array $dmlStack = [];
	/** @var list<mixed> */
	private array $ddlStack = [];

	public function __construct() {
		$this->aliasRegistry = new AliasRegistry();
	}

	/**
	 * @param array<int, array<string, mixed>> $rows
	 */
	public function addToDQLStack(array $rows): void {
		$this->dqlStack[] = $rows;
	}

	/**
	 * @param scalar|array<int, mixed>|null $value
	 */
	public function addToDMLStack($value): void {
		$this->dmlStack[] = $value;
	}

	/**
	 * @param scalar|array<int, mixed>|null $value
	 */
	public function addToDDLStack($value): void {
		$this->ddlStack[] = $value;
	}

	public function getAliasRegistry(): AliasRegistry {
		return $this->aliasRegistry;
	}

	public function getVirtualTables(): VirtualTables {
		$this->virtualTables ??= new VirtualTables();

		return $this->virtualTables;
	}

	public function query(
		#[Language('MySQL')]
		string $query,
	) {
		$statement = $this->prepare($query);
		$statement->execute();

		return $statement;
	}

	public function prepare(
		#[Language('MySQL')]
		string $query,
	) {
		$type = $this->detectType($query);
		$statement = new MockPDOStatement(fn() => $this->popStack($type), $type);

		return new MockQueryStatement($statement, $query);
	}

	private function detectType(string $query): string {
		$prefix = strtolower((string) preg_replace('/\\s+/', '', substr($query, 0, 6)));

		return match (true) {
			str_starts_with($prefix, 'select') => 'dql',
			str_starts_with($prefix, 'insert'),
			str_starts_with($prefix, 'update'),
			str_starts_with($prefix, 'delete') => 'dml',
			default => 'ddl',
		};
	}

	/**
	 * @return mixed
	 */
	private function popStack(string $type) {
		return match ($type) {
			'dql' => $this->popDQL(),
			'dml' => $this->popDML(),
			'ddl' => $this->popDDL(),
			default => null,
		};
	}

	/**
	 * @return mixed
	 * @internal Used by mock statements
	 */
	public function popDQL() {
		return $this->shift($this->dqlStack, 'DQL');
	}

	/**
	 * @template T
	 * @param array<int, T> $stack
	 * @param string $label
	 * @return T
	 */
	private function shift(array &$stack, string $label) {
		if(count($stack) === 0) {
			throw new RuntimeException("{$label} stack is empty");
		}

		return array_shift($stack);
	}

	/**
	 * @return mixed
	 * @internal Used by mock statements
	 */
	public function popDML() {
		$value = $this->shift($this->dmlStack, 'DML');
		$this->lastInsertId = is_scalar($value) ? (string) $value : null;

		return $value;
	}

	/**
	 * @return mixed
	 * @internal Used by mock statements
	 */
	public function popDDL() {
		return $this->shift($this->ddlStack, 'DDL');
	}

	public function exec(
		#[Language('MySQL')]
		string $query,
		array $params = [],
	): int {
		$type = $this->detectType($query);
		if($type === 'dql') {
			return 0;
		}
		$result = $this->popStack($type);
		$this->lastInsertId = is_scalar($result) ? (string) $result : null;

		return (int) $result;
	}

	public function getLastInsertId(?string $name = null): ?string {
		return $this->lastInsertId;
	}

	public function getTableFields(string $table): array {
		return [];
	}

	public function quoteExpression(string $expression, array $arguments = []): string {
		foreach($arguments as $arg) {
			$expression = preg_replace('/\\?/', $this->quote($arg), $expression, 1);
		}

		return (string) $expression;
	}

	public function quote($value): string {
		if($value instanceof DBExpr || $value instanceof QueryStatement) {
			return (string) $value;
		}
		if($value === null) {
			return 'NULL';
		}
		if(is_bool($value)) {
			return $value ? '1' : '0';
		}
		if(is_int($value) || is_float($value)) {
			return (string) $value;
		}
		if(is_array($value)) {
			$quoted = array_map(fn($v) => $this->quote($v), $value);

			return '(' . implode(',', $quoted) . ')';
		}

		return "'" . addslashes((string) $value) . "'";
	}

	public function quoteField(string $field): string {
		return sprintf('`%s`', str_replace('`', '``', $field));
	}

	/**
	 * @param array<int|string, string>|string|null $fields
	 * @return MockRunnableSelect
	 */
	public function select($fields = null) {
		$select = new MockRunnableSelect($this);
		if($fields !== null) {
			$select->fields(is_array($fields) ? $fields : [$fields]);
		}

		return $select;
	}

	public function insert(?array $fields = null) {
		$insert = new Builder\RunnableInsert($this);
		if($fields !== null) {
			$insert->addAll($fields);
		}

		return $insert;
	}

	public function update(?array $fields = null) {
		$update = new Builder\RunnableUpdate($this);
		if($fields !== null) {
			$update->setAll($fields);
		}

		return $update;
	}

	public function delete(): Builder\RunnableDelete {
		return new Builder\RunnableDelete($this);
	}

	public function transactionStart() {
		return $this;
	}

	public function transactionCommit() {
		return $this;
	}

	public function transactionRollback() {
		return $this;
	}

	public function transaction(callable $callback) {
		return $callback($this);
	}

	public function dryRun(callable $callback) {
		return $callback($this);
	}
}
