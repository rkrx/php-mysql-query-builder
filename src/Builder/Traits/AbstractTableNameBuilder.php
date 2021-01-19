<?php
namespace Kir\MySQL\Builder\Traits;

trait AbstractTableNameBuilder {
	/**
	 * @param string|null $alias
	 * @param string $name
	 * @return string
	 */
	abstract protected function buildTableName(?string $alias, string $name): string;
}
