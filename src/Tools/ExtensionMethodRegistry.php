<?php
namespace Kir\MySQL\Tools;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class ExtensionMethodRegistry implements IteratorAggregate {
	/** @var string[] */
	private $extensionMethods = array();

	/**
	 * @param string $alias
	 * @param callable $callable
	 * @return $this
	 */
	public function add($alias, $callable) {
		$this->extensionMethods[$alias] = $callable;
		return $this;
	}

	/**
	 * @param string $alias
	 * @throws \Exception
	 * @return callable
	 */
	public function get($alias) {
		if (!array_key_exists($alias, $this->extensionMethods)) {
			throw new \Exception("Alias not found: {$alias}");
		}
		return $this->extensionMethods[$alias];
	}

	/**
	 * @return callable[]
	 */
	public function getAll() {
		return $this->extensionMethods;
	}

	/**
	 * @return Traversable|callable[]
	 */
	public function getIterator() {
		return new ArrayIterator($this->getAll());
	}
}
