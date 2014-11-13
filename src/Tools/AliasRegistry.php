<?php
namespace Kir\MySQL\Tools;

class AliasRegistry {
	/**
	 * @var string[]
	 */
	private $aliases = array();

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
	 * @throws \Exception
	 * @return string
	 */
	public function get($alias) {
		if(!array_key_exists($alias, $this->aliases)) {
			throw new \Exception("Alias not found: {$alias}");
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