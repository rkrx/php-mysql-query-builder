<?php

namespace Kir\MySQL\Databases\MySQL;

use Kir\MySQL\Exceptions\DatabaseHasGoneAwayException;
use Kir\MySQL\Exceptions\DuplicateUniqueKeyException;
use Kir\MySQL\Exceptions\IntegrityConstraintViolationException;
use Kir\MySQL\Exceptions\LockWaitTimeoutExceededException;
use Kir\MySQL\Exceptions\SqlDeadLockException;
use Kir\MySQL\Exceptions\SqlException;
use PDOException;

class MySQLExceptionInterpreter {
	/**
	 * @param PDOException $exception
	 * @return SqlException
	 */
	public function getMoreConcreteException(PDOException $exception): SqlException {
		$errorInfo = $exception->errorInfo;
		/** @link http://php.net/manual/en/class.exception.php#Hcom115813 (cHao's comment) */
		$code = is_array($errorInfo) && isset($errorInfo[1]) ? ((int) $errorInfo[1]) : ((int) $exception->getCode());
		$message = $exception->getMessage();

		return match ($code) {
			2006 => new DatabaseHasGoneAwayException($message, $code, $exception),
			1213 => new SqlDeadLockException($message, $code, $exception),
			1205 => new LockWaitTimeoutExceededException($message, $code, $exception),
			1022, 1062, 1169, 1586 => new DuplicateUniqueKeyException($message, $code, $exception),
			1216, 1217, 1452 => new IntegrityConstraintViolationException($message, $code, $exception),
			default => new SqlException($message, $code, $exception),
		};
	}
}
