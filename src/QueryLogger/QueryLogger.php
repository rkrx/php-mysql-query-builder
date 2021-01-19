<?php
namespace Kir\MySQL\QueryLogger;

interface QueryLogger {
	/**
	 * @param string $query
	 * @param float $duration Duration in seconds
	 * @return void
	 */
	public function log(string $query, float $duration): void;
}
