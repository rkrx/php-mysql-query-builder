<?php
namespace Kir\MySQL\Builder\Traits;

trait LimitBuilder {
	/** @var int|null */
	private $limit = null;

	/**
	 * @return int|null
	 */
	protected function getLimit() {
		return $this->limit;
	}

	/**
	 * @param int|null $limit
	 * @return $this
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param string $query
	 * @param int|null $offset
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
