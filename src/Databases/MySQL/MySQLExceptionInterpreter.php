<?php
namespace Kir\MySQL\Databases\MySQL;

use PDOException;
use Kir\MySQL\Exceptions\SqlDeadLockException;
use Kir\MySQL\Exceptions\DuplicateUniqueKeyException;
use Kir\MySQL\Exceptions\LockWaitTimeoutExceededException;

class MySQLExceptionInterpreter {
	/**
	 * @param PDOException $exception
	 * @throw PDOException
	 */
	public function throwMoreConcreteException(PDOException $exception) {
		$code = $exception->errorInfo[1];
		$message = $exception->errorInfo[2];
		if($code === 1213) {
			throw new SqlDeadLockException($message, $code, $exception);
		}
		if($code === 1205) {
			throw new LockWaitTimeoutExceededException($message, $code, $exception);
		}
		if($code === 1062) {
			throw new DuplicateUniqueKeyException($message, $code, $exception);
		}
		throw $exception;
	}
}
