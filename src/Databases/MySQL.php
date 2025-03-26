<?php
namespace Kir\MySQL\Databases;

use DateTimeZone;
use JetBrains\PhpStorm\Language;
use Kir\MySQL\Builder;
use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Builder\Select;
use Kir\MySQL\Database;
use Kir\MySQL\Databases\MySQL\MySQLExceptionInterpreter;
use Kir\MySQL\Databases\MySQL\MySQLFieldQuoter;
use Kir\MySQL\Databases\MySQL\MySQLQuoter;
use Kir\MySQL\Databases\MySQL\MySQLRunnableSelect;
use Kir\MySQL\Databases\MySQL\MySQLUUIDGenerator;
use Kir\MySQL\QueryLogger\QueryLoggers;
use Kir\MySQL\Tools\AliasRegistry;
use Kir\MySQL\Tools\VirtualTables;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Stringable;
use Throwable;

/**
 */
class MySQL implements Database {
	/** @var array<string, array<int, string>> */
	private $tableFields = [];
	/** @var PDO */
	private $pdo;
	/** @var bool */
	private $outerTransaction = false;
	/** @var AliasRegistry */
	private $aliasRegistry;
	/** @var int */
	private $transactionLevel = 0;
	/** @var QueryLoggers */
	private $queryLoggers;
	/** @var VirtualTables */
	private $virtualTables;
	/** @var MySQLExceptionInterpreter */
	private $exceptionInterpreter;
	/** @var array<string, mixed> */
	private $options;
	/** @var MySQLQuoter */
	private $quoter;

	/**
	 * @param PDO $pdo
	 * @param array<string, mixed> $options
	 */
	public function __construct(PDO $pdo, array $options = []) {
		if($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_SILENT) {
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		$this->pdo = $pdo;
		$this->aliasRegistry = new AliasRegistry();
		$this->queryLoggers = new QueryLoggers();
		$this->exceptionInterpreter = new MySQLExceptionInterpreter();
		$defaultOptions = [
			'select-options' => [],
			'insert-options' => [],
			'update-options' => [],
			'delete-options' => [],
		];
		$this->options = array_merge($defaultOptions, $options);
		$this->options['timezone'] ??= date_default_timezone_get();
		if(!($this->options['timezone'] instanceof DateTimeZone)) {
			$this->options['timezone'] = new DateTimeZone((string) $this->options['timezone']);
		}
		$this->quoter = new MySQLQuoter($pdo, $this->options['timezone']);
	}

	/**
	 * @return QueryLoggers
	 */
	public function getQueryLoggers(): QueryLoggers {
		return $this->queryLoggers;
	}

	/**
	 * @return AliasRegistry
	 */
	public function getAliasRegistry(): AliasRegistry {
		return $this->aliasRegistry;
	}

	/**
	 * @return VirtualTables
	 */
	public function getVirtualTables(): VirtualTables {
		if($this->virtualTables === null) {
			$this->virtualTables = new VirtualTables();
		}
		return $this->virtualTables;
	}

	/**
	 * @param string $query
	 * @return QueryStatement
	 */
	public function query(
		#[Language('MySQL')]
		string $query
	) {
		return $this->getQueryLoggers()->logRegion($query, fn() =>
			$this->buildQueryStatement($query, fn($query) =>
				$this->pdo->query($query)
			)
		);
	}

	/**
	 * @param string $query
	 * @return QueryStatement
	 */
	public function prepare(
		#[Language('MySQL')]
		string $query
	) {
		return $this->buildQueryStatement((string) $query, fn($query) =>
			$this->pdo->prepare($query)
		);
	}

	/**
	 * @param string $query
	 * @param array<string, null|scalar|Stringable|array<null|scalar>> $params
	 * @return int
	 */
	public function exec(
		#[Language('MySQL')]
		string $query,
		array $params = []
	): int {
		return $this->getQueryLoggers()->logRegion($query, fn() =>
			$this->exceptionHandler(function () use ($query, $params) {
				$stmt = $this->pdo->prepare($query);
				$timer = microtime(true);
				$stmt->execute($params);
				$this->queryLoggers->log($query, microtime(true) - $timer);
				$result = $stmt->rowCount();
				$stmt->closeCursor();
				return $result;
			})
		);
	}

	/**
	 * @param string|null $name
	 * @return string|null
	 */
	public function getLastInsertId(?string $name = null): ?string {
		$result = $this->pdo->lastInsertId();
		if($result === false) {
			return null;
		}
		return $result;
	}

	/**
	 * @param string $table
	 * @return array<int, string>
	 */
	public function getTableFields(string $table): array {
		$fqTable = $this->select()->aliasReplacer()->replace($table);
		if(array_key_exists($fqTable, $this->tableFields)) {
			return $this->tableFields[$fqTable];
		}
		$query = "DESCRIBE {$fqTable}";
		return $this->getQueryLoggers()->logRegion($query, fn() =>
			$this->exceptionHandler(function () use ($query, $fqTable) {
				$stmt = $this->pdo->query($query);
				try {
					if($stmt === false) {
						throw new RuntimeException('Invalid return type');
					}
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$this->tableFields[$fqTable] = array_map(static fn($row) => $row['Field'], $rows ?: []);
					return $this->tableFields[$fqTable];
				} finally {
					try {
						if($stmt instanceof PDOStatement) {
							$stmt->closeCursor();
						}
					} catch (Throwable $e) {}
				}
			})
		);
	}

	/**
	 * @param string $expression
	 * @param array<int, null|scalar|array<int, string>|DBExpr|Select> $arguments
	 * @return string
	 */
	public function quoteExpression(string $expression, array $arguments = []): string {
		return $this->quoter->quoteExpression($expression, $arguments);
	}

	/**
	 * @param null|scalar|array<int, string>|DBExpr|Select $value
	 * @return string
	 */
	public function quote($value): string {
		return $this->quoter->quote($value);
	}

	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteField(string $field): string {
		return MySQLFieldQuoter::quoteField($field);
	}

	/**
	 * @param array<string|int, string>|null $fields
	 * @return MySQLRunnableSelect
	 */
	public function select(?array $fields = null): Builder\RunnableSelect {
		$select = array_key_exists('select-factory', $this->options)
			? call_user_func($this->options['select-factory'], $this, $this->options['select-options'])
			: new MySQL\MySQLRunnableSelect($this, $this->options['select-options']);
		if($fields !== null) {
			$select->fields($fields);
		}
		return $select;
	}

	/**
	 * @param null|array<string|int, string> $fields
	 * @return Builder\RunnableInsert
	 */
	public function insert(?array $fields = null): Builder\RunnableInsert {
		$insert = array_key_exists('insert-factory', $this->options)
			? call_user_func($this->options['insert-factory'], $this, $this->options['insert-options'])
			: new Builder\RunnableInsert($this, $this->options['insert-options']);
		if($fields !== null) {
			$insert->addAll($fields);
		}
		return $insert;
	}

	/**
	 * @param array<string|int, string>|null $fields
	 * @return Builder\RunnableUpdate
	 */
	public function update(?array $fields = null): Builder\RunnableUpdate {
		$update = array_key_exists('update-factory', $this->options)
			? call_user_func($this->options['update-factory'], $this, $this->options['update-options'])
			: new Builder\RunnableUpdate($this, $this->options['update-options']);
		if($fields !== null) {
			$update->setAll($fields);
		}
		return $update;
	}

	/**
	 * @return Builder\RunnableDelete
	 */
	public function delete(): Builder\RunnableDelete {
		return array_key_exists('delete-factory', $this->options)
			? call_user_func($this->options['delete-factory'], $this, $this->options['delete-options'])
			: new Builder\RunnableDelete($this, $this->options['delete-options']);
	}

	/**
	 * @return $this
	 */
	public function transactionStart() {
		if($this->transactionLevel === 0) {
			if($this->pdo->inTransaction()) {
				$this->outerTransaction = true;
			} else {
				$this->pdo->beginTransaction();
			}
		}
		$this->transactionLevel++;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function transactionCommit() {
		return $this->transactionEnd(function () {
			$this->pdo->commit();
		});
	}

	/**
	 * @return $this
	 */
	public function transactionRollback() {
		return $this->transactionEnd(function () {
			$this->pdo->rollBack();
		});
	}

	/**
	 * @template T
	 * @param callable(MySQL): T $callback
	 * @return T
	 */
	public function dryRun(callable $callback) {
		if(!$this->pdo->inTransaction()) {
			$this->transactionStart();
			try {
				return $callback($this);
			} finally {
				$this->transactionRollback();
			}
		} else {
			$uniqueId = MySQLUUIDGenerator::genUUIDv4();
			$this->exec("SAVEPOINT {$uniqueId}");
			try {
				return $callback($this);
			} finally {
				$this->exec("ROLLBACK TO {$uniqueId}");
			}
		}
	}

	/**
	 * @template T
	 * @param callable(MySQL): T $callback
	 * @return T
	 * @throws Throwable
	 */
	public function transaction(callable $callback) {
		if(!$this->pdo->inTransaction()) {
			$this->transactionStart();
			try {
				$result = $callback($this);
				$this->transactionCommit();
				return $result;
			} catch (Throwable $e) {
				if($this->pdo->inTransaction()) {
					$this->transactionRollback();
				}
				throw $e;
			}
		}
		$uniqueId = MySQLUUIDGenerator::genUUIDv4();
		$this->exec("SAVEPOINT {$uniqueId}");
		try {
			$result = $callback($this);
			$this->exec("RELEASE SAVEPOINT {$uniqueId}");
			return $result;
		} catch (Throwable $e) {
			$this->exec("ROLLBACK TO {$uniqueId}");
			throw $e;
		}
	}

	/**
	 * @param callable(): void $fn
	 * @return $this
	 */
	private function transactionEnd($fn): self {
		$this->transactionLevel--;
		if($this->transactionLevel < 0) {
			throw new RuntimeException("Transaction-Nesting-Problem: Trying to invoke commit on a already closed transaction");
		}
		if($this->transactionLevel < 1) {
			if($this->outerTransaction) {
				$this->outerTransaction = false;
			} else {
				$fn();
			}
		}
		return $this;
	}


	/**
	 * @param string $query
	 * @param callable $fn
	 * @return QueryStatement
	 */
	private function buildQueryStatement(string $query, callable $fn): QueryStatement {
		$stmt = $fn($query);
		if(!$stmt) {
			throw new RuntimeException("Could not execute statement:\n{$query}");
		}
		return new QueryStatement($stmt, $query, $this->exceptionInterpreter, $this->queryLoggers);
	}

	/**
	 * @template T
	 * @param callable(): T $fn
	 * @return T
	 */
	private function exceptionHandler(callable $fn) {
		try {
			return $fn();
		} catch (PDOException $exception) {
			throw $this->exceptionInterpreter->getMoreConcreteException($exception);
		}
	}
}
