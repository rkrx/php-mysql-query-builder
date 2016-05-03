<?php
namespace Kir\MySQL\Builder\Traits;

trait OffsetBuilder {
	/** @var int */
	private $offset = null;

	/**
	 * @param int $offset
	 * @return $this
	 */
	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildOffset($query) {
		if($this->offset !== null) {
			$query .= "OFFSET\n\t{$this->offset}\n";
		}
		return $query;
	}
}
