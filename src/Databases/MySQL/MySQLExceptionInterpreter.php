<?php
namespace Kir\MySQL\Databases\MySQL;

use Kir\MySQL\Exceptions\DatabaseHasGoneAwayException;
use Kir\MySQL\Exceptions\IntegrityConstraintViolationException;
use Kir\MySQL\Exceptions\SqlException;
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
		$errorInfo = $exception->errorInfo;
		$code = is_array($errorInfo) && isset($errorInfo[1]) ? ((int) $errorInfo[1]) : ((int) $exception->getCode());
		$message = (string) $exception->getMessage();
		switch($code) {
			case 2006: throw new DatabaseHasGoneAwayException($message, $code, $exception);
			case 1213: throw new SqlDeadLockException($message, $code, $exception);
			case 1205: throw new LockWaitTimeoutExceededException($message, $code, $exception);
			case 1022:
			case 1062:
			case 1169:
			case 1586: throw new DuplicateUniqueKeyException($message, $code, $exception);
			case 1216:
			case 1217:
			case 1452: throw new IntegrityConstraintViolationException($message, (int) $code, $exception);
		}
		/** @link http://php.net/manual/en/class.exception.php#Hcom115813 (cHao's comment) */
		if(!is_string($message) || !is_int($code)) {
			throw new SqlException((string) $message, (int) $code, $exception);
		}
		throw $exception;
	}
}
