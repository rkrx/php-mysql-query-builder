<?php
namespace Kir\MySQL\Builder\Traits;

trait TableNameBuilder {
	use AbstractAliasReplacer;

	/**
	 * @param string $alias
	 * @param string $name
	 * @return string
	 */
	protected function buildTableName($alias, $name) {
		if(is_object($name)) {
			$name = (string) $name;
			$lines = explode("\n", $name);
			foreach($lines as &$line) {
				$line = "\t{$line}";
			}
			$name = join("\n", $lines);
			$name = '(' . trim(rtrim(trim($name), ';')) . ')';
		}
		$name = $this->aliasReplacer()->replace($name);
		if($alias !== null) {
			return sprintf("%s %s", $name, $alias);
		}
		return $name;
	}
}