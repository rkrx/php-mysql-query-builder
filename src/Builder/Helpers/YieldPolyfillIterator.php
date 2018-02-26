<?php
namespace Kir\MySQL\Builder\Helpers;

use Closure;
use Iterator;
use Kir\MySQL\Builder\QueryStatement;
use RuntimeException;

/**
 * @deprecated
 */
class YieldPolyfillIterator implements Iterator {
	/** @var Closure|null */
	private $callback;
	/** @var bool */
	private $preserveTypes;
	/** @var null */
	private $stmt = null;
	/** @var Closure */
	private $statementFactory;
	/** @var mixed */
	private $currentItem;
	/** @var int */
	private $idx = -1;

	/**
	 * @param Closure|null $callback
	 * @param bool $preserveTypes
	 * @param callable $statementFactory
	 */
	public function __construct(Closure $callback = null, $preserveTypes, $statementFactory) {
		$this->callback = $callback;
		$this->preserveTypes = $preserveTypes;
		$this->statementFactory = $statementFactory;
	}

	/**
	 */
	public function __destruct() {
		$this->closeCursor();
	}

	/**
	 * @return mixed
	 */
	public function current() {
		$row = $this->currentItem;
		if($this->preserveTypes) {
			$columnDefinitions = FieldTypeProvider::getFieldTypes($this->getStmt());
			$row = FieldValueConverter::convertValues($row, $columnDefinitions);
		}
		$callback = $this->callback;
		if($callback !== null) {
			$result = $callback($row);
			if($result instanceof DBIgnoreRow) {
				// Do nothing in this case
			} elseif($result !== null) {
				return $result;
			} else {
				return $row;
			}
		}
		return $row;
	}

	/**
	 */
	public function next() {
		$this->idx++;
		$this->currentItem = $this->getStmt()->fetch();
	}

	/**
	 * @return mixed
	 */
	public function key() {
		return $this->idx;
	}

	/**
	 * @return boolean
	 */
	public function valid() {
		$result = !!$this->currentItem;
		if(!$result) {
			$this->closeCursor();
		}
		return $result;
	}

	/**
	 */
	public function rewind() {
		if($this->stmt !== null) {
			throw new RuntimeException("It's not possible to rewind this iterator");
		}
		$this->stmt = call_user_func($this->statementFactory);
		$this->idx = -1;
		$this->next();
	}

	/**
	 * @return QueryStatement
	 */
	private function getStmt() {
		if($this->stmt === null) {
			$this->rewind();
		}
		return $this->stmt;
	}

	/**
	 */
	private function closeCursor() {
		if($this->stmt instanceof QueryStatement) {
			$this->stmt->closeCursor();
		}
		$this->stmt = null;
	}
}
