<?php

namespace Kir\MySQL\QueryLogger;

use Throwable;

interface QueryLogger {
	/**
	 * @param string $query
	 * @param float $duration Duration in seconds
	 * @return void
	 */
	public function log(string $query, float $duration): void;

	/**
	 * @param string $query
	 * @param Throwable $exception
	 * @param float $duration Duration in seconds
	 * @return void
	 */
	public function logError(string $query, Throwable $exception, float $duration): void;
}
