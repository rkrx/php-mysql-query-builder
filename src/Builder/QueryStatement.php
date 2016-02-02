<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Databases\MySQL\MySQLExceptionInterpreter;
use PDO;
use PDOException;
use PDOStatement;
use Kir\MySQL\Database\DatabaseStatement;
use Kir\MySQL\QueryLogger\QueryLoggers;

class QueryStatement implements DatabaseStatement {
	/** @var PDOStatement */
	private $statement;
	/** @var QueryLoggers */
	private $queryLoggers;
	/** @var string */
	private $query;
	/** @var MySQLExceptionInterpreter */
	private $exceptionInterpreter;

	/**
	 * @param PDOStatement $stmt
	 * @param string $query
	 * @param MySQLExceptionInterpreter $exceptionInterpreter
	 * @param QueryLoggers $queryLoggers
	 */
	public function __construct(PDOStatement $stmt, $query, MySQLExceptionInterpreter $exceptionInterpreter, QueryLoggers $queryLoggers) {
		$this->statement = $stmt;
		$this->queryLoggers = $queryLoggers;
		$this->query = $query;
		$this->exceptionInterpreter = $exceptionInterpreter;
	}

	/**
	 * @return PDOStatement
	 */
	public function getStatement() {
		return $this->statement;
	}

	/**
	 * @param array $params
	 * @return bool
	 */
	public function execute(array $params = []) {
		return $this->exceptionHandler(function () use ($params) {
			$timer = microtime(true);
			$response = $this->statement->execute($params);
			$this->queryLoggers->log($this->query, microtime(true) - $timer);
			return $response;
		});
	}

	/**
	 * @return array
	 */
	public function fetchAll() {
		$args = func_get_args();
		return $this->exceptionHandler(function () use ($args) {
			return call_user_func_array([$this->statement, 'fetchAll'], $args);
		});
	}

	/**
	 * @param int $fetchStyle
	 * @param int $cursorOrientation
	 * @param int $cursorOffset
	 * @return mixed
	 */
	public function fetch($fetchStyle = PDO::FETCH_ASSOC, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0) {
		return $this->exceptionHandler(function () use ($fetchStyle, $cursorOrientation, $cursorOffset) {
			return $this->statement->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
		});
	}

	/**
	 * @param int $columnNo
	 * @return mixed
	 */
	public function fetchColumn($columnNo = 0) {
		return $this->exceptionHandler(function () use ($columnNo) {
			return $this->statement->fetchColumn($columnNo);
		});
	}

	/**
	 * @return bool
	 */
	public function closeCursor() {
		return $this->exceptionHandler(function () {
			return $this->statement->closeCursor();
		});
	}

	/**
	 * @return int
	 */
	public function columnCount() {
		return $this->exceptionHandler(function () {
			return $this->statement->columnCount();
		});
	}

	/**
	 * @param int $columnNo
	 * @return array
	 */
	public function getColumnMeta($columnNo) {
		return $this->exceptionHandler(function () use ($columnNo) {
			return $this->statement->getColumnMeta($columnNo);
		});
	}

	/**
	 * @param callable $fn
	 * @return mixed
	 */
	private function exceptionHandler($fn) {
		try {
			return call_user_func($fn);
		} catch (PDOException $e) {
			$this->exceptionInterpreter->throwMoreConcreteException($e);
		}
	}
}
