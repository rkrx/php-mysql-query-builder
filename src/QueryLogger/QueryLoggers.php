<?php
namespace Kir\MySQL\QueryLogger;

class QueryLoggers {
	/** @var QueryLogger[] */
	private $queryLoggers = [];

	/**
	 * @param QueryLogger $queryLogger
	 */
	public function add(QueryLogger $queryLogger) {
		$this->queryLoggers[] = $queryLogger;
	}

	/**
	 * @param string $query
	 * @param float $duration
	 * @return $this
	 */
	public function log(string $query, float $duration) {
		foreach ($this->queryLoggers as $queryLogger) {
			$queryLogger->log($query, $duration);
		}
		return $this;
	}
}
