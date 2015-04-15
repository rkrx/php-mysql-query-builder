<?php
namespace Kir\MySQL\Databases\MySQL;

use Kir\MySQL\Exceptions\SqlDeadLockException;
use PDO;
use PDOException;

class MySQLExceptionInterpreter {
	/** @var PDO */
	private $pdo;

	/**
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * @param PDOException $exception
	 * @throw PDOException
	 */
	public function throwMoreConcreteException(PDOException $exception) {
		$errorInfo = $this->pdo->errorInfo();
		$code = $errorInfo[1];
		$message = $errorInfo[2];
		if($code === 1213) {
			throw new SqlDeadLockException($message, $code, $exception);
		}
		throw $exception;
	}
}