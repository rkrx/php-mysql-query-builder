<?php
namespace Kir\MySQL\Builder\Traits;

trait LimitBuilder {
	/** @var int */
	private $limit = null;

	/**
	 * @return int
	 */
	protected function getLimit() {
		return $this->limit;
	}

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param string $query
	 * @param null $offset
	 * @return string
	 */
	protected function buildLimit($query, $offset = null) {
		$limit = $this->limit;
		if($limit === null && $offset !== null) {
			$limit = '18446744073709551615';
		}
		if($limit !== null) {
			$query .= "LIMIT\n\t{$limit}\n";
		}
		return $query;
	}
}
