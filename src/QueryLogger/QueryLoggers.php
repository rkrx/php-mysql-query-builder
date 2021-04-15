<?php
namespace Kir\MySQL\QueryLogger;

class QueryLoggers {
	/** @var QueryLogger[] */
	private $queryLoggers = [];

	/**
	 * @param QueryLogger $queryLogger
	 */
	public function add(QueryLogger $queryLogger): void {
		$this->queryLoggers[] = $queryLogger;
	}

	/**
	 * @param string $query
	 * @param float $duration
	 */
	public function log(string $query, float $duration): void {
		foreach ($this->queryLoggers as $queryLogger) {
			$queryLogger->log($query, $duration);
		}
	}
}
