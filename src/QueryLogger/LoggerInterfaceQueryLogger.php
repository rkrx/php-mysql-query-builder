<?php
namespace Kir\MySQL\QueryLogger;

use Psr\Log\LoggerInterface;
use Throwable;

class LoggerInterfaceQueryLogger implements QueryLogger {
	public function __construct(
		private LoggerInterface $logger
	) {}

	/**
	 * @inheritDoc
	 */
	public function log(string $query, float $duration): void {
		$this->logger->info(sprintf("Query %s took %0.4f seconds", $query, $duration), ['query' => $query, 'duration' => $duration]);
	}

	/**
	 * @inheritDoc
	 */
	public function logError(string $query, Throwable $exception, float $duration): void {
		$this->logger->error(sprintf("Error'd query %s took %0.4f seconds", $query, $duration), ['query' => $query, 'duration' => $duration, 'exception' => $exception]);
	}
}
