<?php
namespace Kir\MySQL\Databases\MySQL;

use DateTimeInterface;
use DateTimeZone;
use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\Select;
use PDO;

class MySQLQuoter {
	/** @var PDO */
	private $pdo;
	/** @var DateTimeZone */
	private $timeZone;

	public function __construct(PDO $pdo, DateTimeZone $timeZone) {
		$this->timeZone = $timeZone;
		$this->pdo = $pdo;
	}

	/**
	 * @param null|scalar|array<int, null|scalar>|DBExpr|Select|DateTimeInterface $value
	 * @return string
	 */
	public function quote($value): string {
		if(is_null($value)) {
			return 'NULL';
		}

		if(is_bool($value)) {
			return $value ? '1' : '0';
		}

		if(is_array($value)) {
			return implode(', ', array_map([$this, __FUNCTION__], $value));
		}

		if($value instanceof DBExpr) {
			return $value->getExpression();
		}

		if($value instanceof Select) {
			return sprintf('(%s)', (string) $value);
		}

		if(is_int($value) || is_float($value)) {
			return (string) $value;
		}

		if($value instanceof DateTimeInterface) {
			$value = date_create_immutable($value->format('c'))->setTimezone($this->timeZone)->format('Y-m-d H:i:s');
		}

		return $this->pdo->quote($value);
	}

	/**
	 * @param string $expression
	 * @param array<int, null|scalar|array<int, string>|DBExpr|Select> $arguments
	 * @return string
	 */
	public function quoteExpression(string $expression, array $arguments = []): string {
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
		return (string) preg_replace_callback('{(\\?)}', $func, $expression);
	}
}
