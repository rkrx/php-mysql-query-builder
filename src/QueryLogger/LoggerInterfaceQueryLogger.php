<?php
namespace Kir\MySQL\QueryLogger;

use Psr\Log\LoggerInterface;

class LoggerInterfaceQueryLogger implements QueryLogger {
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param LoggerInterface $logger
	 */
	function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param string $query
	 * @param float $duration Duration in seconds
	 * @return void
	 */
	public function log($query, $duration) {
		$this->logger->info(sprintf("Query %s took %0.4f seconds", $query, $duration), ['query' => $query, 'duration' => $duration]);
	}
}