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
		switch($code) {
			case 2006: return new DatabaseHasGoneAwayException($message, $code, $exception);
			case 1213: return new SqlDeadLockException($message, $code, $exception);
			case 1205: return new LockWaitTimeoutExceededException($message, $code, $exception);
			case 1022:
			case 1062:
			case 1169:
			case 1586: return new DuplicateUniqueKeyException($message, $code, $exception);
			case 1216:
			case 1217:
			case 1452: return new IntegrityConstraintViolationException($message, $code, $exception);
		}
		return new SqlException($message, $code, $exception);
	}
}
