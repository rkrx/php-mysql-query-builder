<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Databases\MySQL;

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
		if(is_array($name)) {
			$parts = [];
			foreach($name as $bucket) {
				$values = [];
				foreach($bucket as $field => $value) {
					$values[] = sprintf('%s AS %s', $this->db()->quote($value), $this->db()->quoteField($field));
				}
				$parts[] = sprintf("SELECT %s", join(', ', $values));
			}
			$name = '(' . join("\n\tUNION\n\t", $parts) . ')';
		}
		if($this->db()->getVirtualTables()->has($name)) {
			$select = (string )$this->db()->getVirtualTables()->get($name);
			$name = sprintf('(%s)', join("\n\t", explode("\n", trim($select))));
		}
		$name = $this->aliasReplacer()->replace($name);
		if($alias !== null) {
			return sprintf("%s %s", $name, $alias);
		}
		return $name;
	}
	
	/**
	 * @return MySQL
	 */
	abstract protected function db();
}
