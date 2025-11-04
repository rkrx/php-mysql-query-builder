<?php
namespace Kir\MySQL\Builder\Traits;

use InvalidArgumentException;
use Kir\MySQL\Builder\Internal\Types;
use Kir\MySQL\Common\SpecialTable;
use Kir\MySQL\Database;
use Kir\MySQL\Tools\VirtualTable;

/**
 * @phpstan-import-type DBTableNameType from Types
 */
trait TableNameBuilder {
	use AbstractAliasReplacer;

	/**
	 * @param string|null $alias
	 * @param DBTableNameType $name
	 * @return string
	 */
	protected function buildTableName(?string $alias, $name): string {
		if($name instanceof SpecialTable) {
			$name = $name->asString($this->db());
		} elseif(is_object($name) && !($name instanceof VirtualTable) && method_exists($name, '__toString')) {
			$name = (string) $name;
			$lines = explode("\n", $name);
			$lines = array_map(static fn(string $line) => "\t{$line}", $lines);
			$name = implode("\n", $lines);
			$name = '(' . trim(rtrim(trim($name), ';')) . ')';
		} elseif(is_array($name)) {
			$parts = [];
			foreach($name as /*$index => */$bucket) {
				if(is_scalar($bucket)/* && ctype_digit((string) $index)*/) {
					$parts[] = "SELECT {$this->db()->quote($bucket)} AS {$this->db()->quoteField('value')}";
				} elseif(is_iterable($bucket)) {
					$values = [];
					foreach($bucket as $field => $value) {
						$values[] = sprintf('%s AS %s', $this->db()->quote($value), $this->db()->quoteField($field));
					}
					$parts[] = sprintf("SELECT %s", implode(', ', $values));
				} else {
					throw new InvalidArgumentException('Only scalar values and iterables are supported as table data');
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
	abstract protected function db(): Database;
}
