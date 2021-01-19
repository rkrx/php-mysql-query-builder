<?php
namespace Kir\MySQL\Builder\Traits;

trait LimitBuilder {
	/** @var int|null */
	private $limit;

	/**
	 * @return int|null
	 */
	protected function getLimit(): ?int {
		return $this->limit;
	}

	/**
	 * @param int|null $limit
	 * @return $this
	 */
	public function limit(?int $limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param string $query
	 * @param int|null $offset
	 * @return string
	 */
	protected function buildLimit(string $query, ?int $offset = null) {
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
