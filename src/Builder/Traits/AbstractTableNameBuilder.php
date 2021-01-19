<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Select;
use Kir\MySQL\Tools\VirtualTable;

trait AbstractTableNameBuilder {
	/**
	 * @param string|null $alias
	 * @param string|array|object|Select|VirtualTable $name
	 * @return string
	 */
	abstract protected function buildTableName(?string $alias, $name): string;
}
