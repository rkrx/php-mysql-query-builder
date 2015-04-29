<?php
namespace Kir\MySQL\QueryLogger;

class ClosureQueryLogger implements QueryLogger {
	/** @var callable */
	private $fn;

	/**
	 * @param callable $fn
	 */
	public function __construct($fn) {
		$this->fn = $fn;
	}

	/**
	 * @param string $query
	 * @param float $duration Duration in seconds
	 * @return void
	 */
	public function log($query, $duration) {
		call_user_func($this->fn, $query, $duration);
	}
}
