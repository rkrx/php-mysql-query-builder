<?php
namespace Kir\MySQL\QueryLogger;

use Throwable;

class ClosureQueryLogger implements QueryLogger {
	/** @var callable(string, float, string, Throwable|null): void */
	private $fn;

	/**
	 * @param callable(string, float, string, Throwable|null): void $fn
	 */
	public function __construct(callable $fn) {
		$this->fn = $fn;
	}

	/**
	 * @inheritDoc
	 */
	public function log(string $query, float $duration): void {
		call_user_func($this->fn, $query, $duration, 'INFO', null);
	}

	/**
	 * @inheritDoc
	 */
	public function logError(string $query, Throwable $exception, float $duration): void {
		call_user_func($this->fn, $query, $duration, 'ERROR', $exception);
	}
}
