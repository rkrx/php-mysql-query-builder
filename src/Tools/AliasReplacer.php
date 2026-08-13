<?php

namespace Kir\MySQL\Tools;

class AliasReplacer {
	private const QUOTED_QUERY_PART_PATTERN = <<<'REGEX'
{("(?:\\.|""|[^"\\])*"|'(?:\\.|''|[^'\\])*'|`(?:\\.|``|[^`\\])*`)}
REGEX;

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

	/**
	 * Replace registered aliases in the unquoted parts of a raw SQL query.
	 *
	 * @param string $query
	 * @return string
	 */
	public function replaceQuery(string $query): string {
		if(!str_contains($query, '#') || preg_match('{\\w#\\w}', $query) !== 1) {
			return $query;
		}

		$parts = preg_split(self::QUOTED_QUERY_PART_PATTERN, $query, -1, PREG_SPLIT_DELIM_CAPTURE);
		if($parts === false) {
			return $query;
		}

		$aliases = $this->aliasRegistry->getAll();
		foreach($parts as $index => $part) {
			if($index % 2 !== 0) {
				continue;
			}
			$parts[$index] = (string) preg_replace_callback(
				'{\\b(\\w+)#(\\w+)\\b}',
				static function(array $matches) use ($aliases): string {
					[, $alias, $table] = $matches;
					if(!array_key_exists($alias, $aliases)) {
						return $matches[0];
					}

					return $aliases[$alias] . $table;
				},
				$part,
			);
		}

		return implode('', $parts);
	}
}
