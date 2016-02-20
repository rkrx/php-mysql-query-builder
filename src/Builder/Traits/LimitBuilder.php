<?php
namespace Kir\MySQL\Builder\Traits;

trait LimitBuilder {
	/** @var int */
	private $limit = null;

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildLimit($query) {
		if($this->limit !== null) {
			$query .= "LIMIT\n\t{$this->limit}\n";
		}
		return $query;
	}
}
