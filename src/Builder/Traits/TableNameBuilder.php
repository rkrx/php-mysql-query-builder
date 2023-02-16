<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Select;
use Kir\MySQL\Database;
use Kir\MySQL\Tools\VirtualTable;

trait TableNameBuilder {
	use AbstractAliasReplacer;

	/**
	 * @param string|null $alias
	 * @param string|array<int, mixed|array<string, mixed>>|object|Select|VirtualTable $name
	 * @return string
	 */
	protected function buildTableName(?string $alias, $name): string {
		if(is_object($name) && !($name instanceof VirtualTable) && method_exists($name, '__toString')) {
			$name = (string) $name;
			$lines = explode("\n", $name);
			$lines = array_map(static function (string $line) { return "\t{$line}"; }, $lines);
			$name = implode("\n", $lines);
			$name = '(' . trim(rtrim(trim($name), ';')) . ')';
		}
		if(is_array($name)) {
			$parts = [];
			foreach($name as $index => $bucket) {
				if(is_scalar($bucket) && ctype_digit((string) $index)) {
					$parts[] = "SELECT {$this->db()->quote($bucket)} AS {$this->db()->quoteField('value')}";
				} else {
					$values = [];
					foreach($bucket as $field => $value) {
						$values[] = sprintf('%s AS %s', $this->db()->quote($value), $this->db()->quoteField($field));
					}
					$parts[] = sprintf("SELECT %s", implode(', ', $values));
				}
			}
			$name = '(' . implode("\n\tUNION ALL\n\t", $parts) . ')';
		}
		if((is_string($name) || $name instanceof VirtualTable) && $this->db()->getVirtualTables()->has($name)) {
			$select = (string) $this->db()->getVirtualTables()->get($name);
			$name = sprintf('(%s)', implode("\n\t", explode("\n", trim($select))));
		}
		$name = $this->aliasReplacer()->replace((string) $name);
		if($alias !== null) {
			return sprintf("%s %s", $name, $alias);
		}
		return $name;
	}

	/**
	 * @return Database
	 */
	abstract protected function db();
}
