<?php
namespace Kir\MySQL\Databases;

use PDO;
use PDOException;
use UnexpectedValueException;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Builder;
use Kir\MySQL\Builder\Exception;
use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Database;
use Kir\MySQL\Databases\MySQL\MySQLExceptionInterpreter;
use Kir\MySQL\QueryLogger\QueryLoggers;
use Kir\MySQL\Tools\AliasRegistry;

/**
 */
class MySQL implements Database {
	/** @var array */
	private static $tableFields = array();
	/** @var PDO */
	private $pdo;
	/** @var bool */
	private $outerTransaction = false;
	/** @var AliasRegistry */
	private $aliasRegistry;
	/** @var int */
	private $transactionLevel = 0;
	/** @var QueryLoggers */
	private $queryLoggers = 0;
	/** @var MySQLExceptionInterpreter */
	private $exceptionInterpreter = 0;

	/**
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo) {
		$this->pdo = $pdo;
		$this->aliasRegistry = new AliasRegistry();
		$this->queryLoggers = new QueryLoggers();
		$this->exceptionInterpreter = new MySQLExceptionInterpreter($pdo);
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
	 * @param string $query
	 * @throws Exception
	 * @return QueryStatement
	 */
	public function query($query) {
		return $this->buildQueryStatement($query, function ($query) {
			$stmt = $this->pdo->query($query);
			return $stmt;
		});
	}

	/**
	 * @param string $query
	 * @throws Exception
	 * @return QueryStatement
	 */
	public function prepare($query) {
		return $this->buildQueryStatement($query, function ($query) {
			$stmt = $this->pdo->prepare($query);
			return $stmt;
		});
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @return int
	 */
	public function exec($query, array $params = array()) {
		try {
			$stmt = $this->pdo->prepare($query);
			$timer = microtime(true);
			$stmt->execute($params);
			$this->queryLoggers->log($query, microtime(true) - $timer);
			$result = $stmt->rowCount();
			$stmt->closeCursor();
			return $result;
		} catch (PDOException $e) {
			$this->exceptionInterpreter->throwMoreConcreteException($e);
			throw $e;
		}
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
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		self::$tableFields[$table] = array_map(function ($row) { return $row['Field']; }, $rows);
		$stmt->closeCursor();
		return self::$tableFields[$table];
	}

	/**
	 * @param mixed $expression
	 * @param array $arguments
	 * @return string
	 */
	public function quoteExpression($expression, array $arguments = array()) {
		$func = function ($oldValue) use ($arguments) {
			static $idx = -1;
			$idx++;
			$index = $idx;

			if(substr($oldValue[0], 0, 1) == ':') {
				$index = (int) substr($oldValue[0], 1);
			}

			if(array_key_exists($index, $arguments)) {
				$argument = $arguments[$index];
				$value = $this->quote($argument);
			} else {
				$value = 'NULL';
			}
			return $value;
		};
		$result = preg_replace_callback('/(\\?|:\\d+)/', $func, $expression);
		return (string) $result;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function quote($value) {
		if(is_null($value)) {
			$result = 'NULL';
		} elseif($value instanceof Builder\Select) {
			$result = sprintf('(%s)', (string) $value);
		} elseif(is_array($value)) {
			$result = join(', ', array_map(function ($value) { return $this->quote($value); }, $value));
		/*} elseif(is_int(trim($value)) && strpos('123456789', substr(0, 1, trim($value))) !== null) {
			$result = $value;*/
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
			return (string) $field;
		}
		$parts = explode('.', $field);
		return '`'.join('`.`', $parts).'`';
	}

	/**
	 * @param array $fields
	 * @return RunnableSelect
	 */
	public function select(array $fields = null) {
		$select = new RunnableSelect($this);
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
		$insert = new Builder\RunnableInsert($this);
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
		$update = new Builder\RunnableUpdate($this);
		if($fields !== null) {
			$update->setAll($fields);
		}
		return $update;
	}

	/**
	 * @return Builder\RunnableDelete
	 */
	public function delete() {
		return new Builder\RunnableDelete($this);
	}

	/**
	 * @return $this
	 */
	public function transactionStart() {
		if((int) $this->transactionLevel === 0) {
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
	 * @throws \Exception
	 */
	public function transactionCommit() {
		return $this->transactionEnd(function () {
			$this->pdo->commit();
		});
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	public function transactionRollback() {
		return $this->transactionEnd(function () {
			$this->pdo->rollBack();
		});
	}

	/**
	 * @param int|callable $tries
	 * @param callable|null $callback
	 * @return mixed
	 * @throws \Exception
	 * @throws null
	 */
	public function transaction($tries = 1, $callback = null) {
		if(is_callable($tries)) {
			$callback = $tries;
			$tries = 1;
		} elseif(!is_callable($callback)) {
			throw new \Exception('$callback must be a callable');
		}
		$e = null;
		for(; $tries--;) {
			try {
				$this->transactionStart();
				$result = call_user_func($callback, $this);
				$this->transactionCommit();
				return $result;
			} catch (\Exception $e) {
				$this->transactionRollback();
			}
		}
		throw $e;
	}

	/**
	 * @param callable $fn
	 * @return $this
	 * @throws \Exception
	 */
	private function transactionEnd($fn) {
		$this->transactionLevel--;
		if($this->transactionLevel < 0) {
			throw new \Exception("Transaction-Nesting-Problem: Trying to invoke commit on a already closed transaction");
		}
		if((int) $this->transactionLevel === 0) {
			if($this->outerTransaction) {
				$this->outerTransaction = false;
			} else {
				call_user_func($fn);
			}
		}
		return $this;
	}

	/**
	 * @param string $query
	 * @param callable $fn
	 * @return QueryStatement
	 * @throws Exception
	 */
	private function buildQueryStatement($query, $fn) {
		$stmt = call_user_func($fn, $query);
		if(!$stmt) {
			throw new Exception("Could not execute statement:\n{$query}");
		}
		$stmtWrapper = new QueryStatement($stmt, $query, $this->queryLoggers);
		return $stmtWrapper;
	}
}
