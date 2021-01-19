<?php
namespace Kir\MySQL\Builder\Traits;

trait OffsetBuilder {
	/** @var int|null */
	private $offset = null;

	/**
	 * @return int
	 */
	protected function getOffset(): ?int {
		return $this->offset;
	}

	/**
	 * @param int|null $offset
	 * @return $this
	 */
	public function offset(?int $offset = 0) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildOffset(string $query): string {
		if($this->offset !== null) {
			$query .= "OFFSET\n\t{$this->offset}\n";
		}
		return $query;
	}
}
