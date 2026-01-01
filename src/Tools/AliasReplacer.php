<?php

namespace Kir\MySQL\Tools;

class AliasReplacer {
	public function __construct(
		private AliasRegistry $aliasRegistry,
	) {}

	/**
	 * @param string $name
	 * @return string
	 */
	public function replace(string $name): string {
		$fn = function($values) {
			[, $alias, $part] = $values;
			$string = $this->aliasRegistry->get($alias);

			return $string . $part;
		};

		return (string) preg_replace_callback('{^(\\w+)#(\\w+.*)$}', $fn, $name);
	}
}
