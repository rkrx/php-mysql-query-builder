<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\QueryLogger\QueryLoggers;
use PDO;
use PDOStatement;

class QueryStatement {
	/** @var PDOStatement */
	private $statement;
	/** @var QueryLoggers */
	private $queryLoggers;
	/** @var string */
	private $query;

	/**
	 * @param PDOStatement $stmt
	 * @param string $query
	 * @param QueryLoggers $queryLoggers
	 */
	public function __construct(PDOStatement $stmt, $query, QueryLoggers $queryLoggers) {
		$this->statement = $stmt;
		$this->queryLoggers = $queryLoggers;
		$this->query = $query;
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
		$timer = microtime(true);
		$response = $this->statement->execute($params);
		$this->queryLoggers->log($this->query, microtime(true) - $timer);
		return $response;
	}

	/**
	 * @return array
	 */
	public function fetchAll() {
		$args = func_get_args();
		return call_user_func_array([$this->statement, 'fetchAll'], $args);
	}

	/**
	 * @param int $fetchStyle
	 * @param int $cursorOrientation
	 * @param int $cursorOffset
	 * @return mixed
	 */
	public function fetch($fetchStyle = PDO::FETCH_ASSOC, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0) {
		return $this->statement->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
	}

	/**
	 * @param int $columnNo
	 * @return mixed
	 */
	public function fetchColumn($columnNo = 0) {
		return $this->statement->fetchColumn($columnNo);
	}

	/**
	 * @return bool
	 */
	public function closeCursor() {
		return $this->statement->closeCursor();
	}

	/**
	 * @return int
	 */
	public function columnCount() {
		return $this->statement->columnCount();
	}

	/**
	 * @param int $columnNo
	 * @return array
	 */
	public function getColumnMeta($columnNo) {
		return $this->statement->getColumnMeta($columnNo);
	}
}