<?php
namespace Kir\MySQL\QueryLogger;

use Throwable;

class QueryLoggers {
	/** @var QueryLogger[] */
	private array $queryLoggers = [];

	public function add(QueryLogger $queryLogger): void {
		$this->queryLoggers[] = $queryLogger;
	}

	/**
	 * @template T
	 * @param string $query
	 * @param callable(): T $fn
	 * @return T
	 */
	public function logRegion(string $query, $fn) {
		$exception = null;
		$timer = microtime(true);
		try {
			return $fn();
		} catch(Throwable $e) {
			$exception = $e;
			throw $e;
		} finally {
			$finalTimer = microtime(true) - $timer;
			if($exception === null) {
				$this->log($query, $finalTimer);
			} else {
				$this->logError($query, $exception, $finalTimer);
			}
		}
	}

	/**
	 * @param string $query
	 * @param float $duration
	 */
	public function log(string $query, float $duration): void {
		foreach ($this->queryLoggers as $queryLogger) {
			try {
				$queryLogger->log($query, $duration);
			} catch (Throwable $e) {}
		}
	}

	/**
	 * @param string $query
	 * @param Throwable $exception
	 * @param float $duration
	 */
	public function logError(string $query, Throwable $exception, float $duration): void {
		foreach ($this->queryLoggers as $queryLogger) {
			try {
				$queryLogger->logError($query, $exception, $duration);
			} catch (Throwable $e) {}
		}
	}
}
