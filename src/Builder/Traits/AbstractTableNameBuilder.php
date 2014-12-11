<?php
namespace Kir\MySQL\Builder\Traits;

trait AbstractTableNameBuilder {
	/**
	 * @param string $alias
	 * @param string $name
	 * @return string
	 */
	abstract protected function buildTableName($alias, $name);
}