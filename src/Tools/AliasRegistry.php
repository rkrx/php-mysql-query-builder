<?php
namespace Kir\MySQL\Tools;

use RuntimeException;

class AliasRegistry {
	/** @var string[] */
	private $aliases = [];

	/**
	 * @param string $alias
	 * @param string $string
	 * @return $this
	 */
	public function add($alias, $string) {
		$this->aliases[$alias] = $string;
		return $this;
	}

	/**
	 * @param string $alias
	 * @return string
	 */
	public function get($alias) {
		if (!array_key_exists($alias, $this->aliases)) {
			throw new RuntimeException("Alias not found: {$alias}");
		}
		return $this->aliases[$alias];
	}

	/**
	 * @return string[]
	 */
	public function getAll() {
		return $this->aliases;
	}
}
