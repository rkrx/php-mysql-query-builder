<?php
namespace Kir\MySQL\Databases;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use Kir\MySQL\Builder;
use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Database;
use Kir\MySQL\Databases\MySQL\MySQLExceptionInterpreter;
use Kir\MySQL\QueryLogger\QueryLoggers;
use Kir\MySQL\Tools\AliasRegistry;
use Kir\MySQL\Tools\VirtualTables;

/**
 */
class MySQL implements Database {
	/** @var array */
	private static $tableFields = [];
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
	/** @var array */
	private $options;

	/**
	 * @param PDO $pdo
	 * @param array $options
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
	}

	/**
	 * @return QueryLoggers
	 */
	public function getQueryLoggers() {
		return $this->queryLoggers;
	}

	/**
	 * @return AliasRegistry
	 */
	public function getAliasRegistry() {
		return $this->aliasRegistry;
	}

	/**
	 * @return VirtualTables
	 */
	public function getVirtualTables() {
		if($this->virtualTables === null) {
			$this->virtualTables = new VirtualTables();
		}
		return $this->virtualTables;
	}

	/**
	 * @param string $query
	 * @return QueryStatement
	 */
	public function query($query) {
		return $this->buildQueryStatement($query, function ($query) {
			return $this->pdo->query($query);
		});
	}

	/**
	 * @param string|object $query
	 * @return QueryStatement
	 */
	public function prepare($query) {
		return $this->buildQueryStatement((string) $query, function ($query) {
			return $this->pdo->prepare($query);
		});
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @return int
	 */
	public function exec($query, array $params = []) {
		return $this->exceptionHandler(function () use ($query, $params) {
			$stmt = $this->pdo->prepare($query);
			$timer = microtime(true);
			$stmt->execute($params);
			$this->queryLoggers->log($query, microtime(true) - $timer);
			$result = $stmt->rowCount();
			$stmt->closeCursor();
			return $result;
		});
	}

	/**
	 * @return string
	 */
	public function getLastInsertId() {
		return $this->pdo->lastInsertId();
	}

	/**
	 * @param string $table
	 * @return array
	 */
	public function getTableFields($table) {
		$table = $this->select()->aliasReplacer()->replace($table);
		if(array_key_exists($table, self::$tableFields)) {
			return self::$tableFields[$table];
		}
		$stmt = $this->pdo->query("DESCRIBE {$table}");
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		self::$tableFields[$table] = array_map(static function ($row) { return $row['Field']; }, $rows);
		$stmt->closeCursor();
		return self::$tableFields[$table];
	}

	/**
	 * @param mixed $expression
	 * @param array $arguments
	 * @return string
	 */
	public function quoteExpression($expression, array $arguments = array()) {
		$index = -1;
		$func = function () use ($arguments, &$index) {
			$index++;
			if(array_key_exists($index, $arguments)) {
				$argument = $arguments[$index];
				$value = $this->quote($argument);
			} elseif(count($arguments) > 0) {
				$args = $arguments;
				$value = array_pop($args);
				$value = $this->quote($value);
			} else {
				$value = 'NULL';
			}
			return $value;
		};
		$result = preg_replace_callback('/(\\?)/', $func, $expression);
		return (string) $result;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function quote($value) {
		if(is_null($value)) {
			$result = 'NULL';
		} elseif($value instanceof Builder\DBExpr) {
			$result = $value->getExpression();
		} elseif($value instanceof Builder\Select) {
			$result = sprintf('(%s)', (string) $value);
		} elseif(is_array($value)) {
			$result = implode(', ', array_map(function ($value) { return $this->quote($value); }, $value));
		} else {
			$result = $this->pdo->quote($value);
		}
		return $result;
	}

	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteField($field) {
		if (is_numeric($field) || !is_string($field)) {
			throw new UnexpectedValueException('Field name is invalid');
		}
		if(strpos($field, '`') !== false) {
			return $field;
		}
		$parts = explode('.', $field);
		return '`'.implode('`.`', $parts).'`';
	}

	/**
	 * @param array $fields
	 * @return Builder\RunnableSelect
	 */
	public function select(array $fields = null) {
		$select = array_key_exists('select-factory', $this->options)
			? call_user_func($this->options['select-factory'], $this, $this->options['select-options'])
			: new Builder\RunnableSelect($this, $this->options['select-options']);
		if($fields !== null) {
			$select->fields($fields);
		}
		return $select;
	}

	/**
	 * @param array $fields
	 * @return Builder\RunnableInsert
	 */
	public function insert(array $fields = null) {
		$insert = array_key_exists('insert-factory', $this->options)
			? call_user_func($this->options['insert-factory'], $this, $this->options['insert-options'])
			: new Builder\RunnableInsert($this, $this->options['insert-options']);
		if($fields !== null) {
			$insert->addAll($fields);
		}
		return $insert;
	}

	/**
	 * @param array $fields
	 * @return Builder\RunnableUpdate
	 */
	public function update(array $fields = null) {
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
	public function delete() {
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
	 * @param callable|null $callback
	 * @return mixed
	 */
	public function dryRun($callback = null) {
		if(!$this->pdo->inTransaction()) {
			$this->transactionStart();
			try {
				return $callback($this);
			} finally {
				$this->transactionRollback();
			}
		} else {
			$uniqueId = $this->genUniqueId();
			$this->exec("SAVEPOINT {$uniqueId}");
			try {
				return $callback($this);
			} finally {
				$this->exec("ROLLBACK TO {$uniqueId}");
			}
		}
	}

	/**
	 * @param callable|null $callback
	 * @return mixed
	 */
	public function transaction(callable $callback = null) {
		$result = null;
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
		$uniqueId = $this->genUniqueId();
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
	 * @param callable $fn
	 * @return $this
	 */
	private function transactionEnd($fn) {
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
	private function buildQueryStatement($query, $fn) {
		$stmt = $fn($query);
		if(!$stmt) {
			throw new RuntimeException("Could not execute statement:\n{$query}");
		}
		return new QueryStatement($stmt, $query, $this->exceptionInterpreter, $this->queryLoggers);
	}

	/**
	 * @param callable $fn
	 * @return mixed
	 */
	private function exceptionHandler($fn) {
		try {
			return $fn();
		} catch (PDOException $e) {
			$this->exceptionInterpreter->throwMoreConcreteException($e);
		}
		return null;
	}

	/**
	 * @return string
	 */
	private function genUniqueId() {
		// Generate a unique id from a former random-uuid-generator
		return sprintf('ID%04x%04x%04x%04x%04x%04x%04x%04x',
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);
	}
}
