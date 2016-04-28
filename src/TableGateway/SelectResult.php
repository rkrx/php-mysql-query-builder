<?php
namespace Kir\MySQL\TableGateway;

use IteratorAggregate;
use Kir\MySQL\Builder\RunnableSelect;
use Traversable;

class SelectResult implements IteratorAggregate {
	/** @var RunnableSelect */
	private $select;
	/** @var array */
	private $options;

	/**
	 * @param RunnableSelect $select
	 * @param array $options
	 */
	public function __construct(RunnableSelect $select, array $options = []) {
		$this->select = $select;
		$this->options = $options;
	}

	/**
	 * @return RunnableSelect
	 */
	public function query() {
		return $this->select;
	}

	/**
	 * @return int
	 */
	public function getFoundRows() {
		return $this->getFoundRows();
	}

	/**
	 * @return Traversable|array[]
	 */
	public function getIterator() {
		return $this->select->getIterator();
	}
}
