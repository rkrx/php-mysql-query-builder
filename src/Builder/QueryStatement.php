<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Database\DatabaseStatement;
use Kir\MySQL\Databases\MySQL\MySQLExceptionInterpreter;
use Kir\MySQL\Exceptions\SqlException;
use Kir\MySQL\QueryLogger\QueryLoggers;
use PDO;
use PDOException;
use PDOStatement;

class QueryStatement implements DatabaseStatement {
	/** @var PDOStatement<mixed> */
	private $statement;
	/** @var QueryLoggers */
	private $queryLoggers;
	/** @var string */
	private $query;
	/** @var MySQLExceptionInterpreter */
	private $exceptionInterpreter;

	/**
	 * @param PDOStatement<mixed> $stmt
	 * @param string $query
	 * @param MySQLExceptionInterpreter $exceptionInterpreter
	 * @param QueryLoggers $queryLoggers
	 */
	public function __construct(PDOStatement $stmt, string $query, MySQLExceptionInterpreter $exceptionInterpreter, QueryLoggers $queryLoggers) {
		$this->statement = $stmt;
		$this->queryLoggers = $queryLoggers;
		$this->query = $query;
		$this->exceptionInterpreter = $exceptionInterpreter;
	}

	/**
	 * @return PDOStatement<mixed>
	 */
	public function getStatement(): PDOStatement {
		return $this->statement;
	}

	/**
	 * @param int $mode
	 * @param mixed $arg0
	 * @param array<mixed, mixed>|null $arg1
	 * @return $this
	 */
	public function setFetchMode(int $mode = PDO::FETCH_ASSOC, $arg0 = null, ?array $arg1 = null) {
		$args = [$mode];
		if($arg0 !== null) {
			$args[] = $arg0;
		}
		if($arg1 !== null) {
			$args[] = $arg1;
		}
		$this->statement->setFetchMode(...$args);
		return $this;
	}

	/**
	 * @param array<string, mixed> $params
	 * @throws SqlException
	 * @return $this
	 */
	public function execute(array $params = []) {
		$this->exceptionHandler(function() use ($params) {
			$this->queryLoggers->logRegion($this->query, function() use ($params) {
				$response = $this->statement->execute($params);
				if(!$response) {
					throw new SqlException('Execution returned with "false".');
				}
			});
		});
		return $this;
	}

	/**
	 * @param int $fetchStyle
	 * @param mixed|null $fetchArgument
	 * @param mixed[] $ctorArgs
	 * @return array<mixed, mixed>
	 */
	public function fetchAll($fetchStyle = PDO::FETCH_ASSOC, $fetchArgument = null, array $ctorArgs = []): array {
		$result = $x = $this->exceptionHandler(function() use ($fetchStyle, $fetchArgument, $ctorArgs) {
			if($fetchArgument !== null) {
				return $this->statement->fetchAll($fetchStyle, $fetchArgument, ...$ctorArgs);
			}
			return $this->statement->fetchAll($fetchStyle);
		});
		/** @var array<mixed, mixed>|false $x */
		if($x === false) {
			return [];
		}
		return $result;
	}

	/**
	 * @param int $fetchStyle
	 * @param int $cursorOrientation
	 * @param int $cursorOffset
	 * @return mixed
	 */
	public function fetch($fetchStyle = PDO::FETCH_ASSOC, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0) {
		return $this->exceptionHandler(fn() => $this->statement->fetch($fetchStyle, $cursorOrientation, $cursorOffset));
	}

	/**
	 * @param int $columnNo
	 * @return mixed
	 */
	public function fetchColumn($columnNo = 0) {
		return $this->exceptionHandler(fn() => $this->statement->fetchColumn($columnNo));
	}

	/**
	 * @return bool
	 */
	public function closeCursor(): bool {
		return $this->exceptionHandler(fn() => $this->statement->closeCursor());
	}

	/**
	 * @return int
	 */
	public function columnCount(): int {
		return $this->exceptionHandler(fn() => $this->statement->columnCount());
	}

	/**
	 * @param int $columnNo
	 * @return null|array<string, mixed>
	 */
	public function getColumnMeta(int $columnNo): ?array {
		return $this->exceptionHandler(function() use ($columnNo) {
			$columnMeta = $this->statement->getColumnMeta($columnNo);
			if($columnMeta === false) {
				return null;
			}
			return $columnMeta;
		});
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
