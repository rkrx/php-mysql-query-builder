<?php
namespace Kir\MySQL\Tools;

class AliasReplacer {
	/** @var AliasRegistry */
	private $aliasRegistry;

	/**
	 * @param AliasRegistry $aliasRegistry
	 */
	public function __construct(AliasRegistry $aliasRegistry) {
		$this->aliasRegistry = $aliasRegistry;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function replace(string $name): string {
		$fn = function($values) {
			list(, $alias, $part) = $values;
			$string = $this->aliasRegistry->get($alias);
			return $string.$part;
		};
		return preg_replace_callback('/^(\\w+)#(\\w+)$/', $fn, $name);
	}
}
