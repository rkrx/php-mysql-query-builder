<?php

namespace Kir\MySQL\Builder;

use IteratorAggregate;
use Kir\MySQL\Tools\AliasReplacer;
use Traversable;

/**
 * @extends IteratorAggregate<int, array<string, mixed>>
 */
interface RunnableSelect extends Select, PreparedSelect, IteratorAggregate {
	/**
	 * @return AliasReplacer
	 */
	public function aliasReplacer(): AliasReplacer;

	/**
	 * @param bool $preserveTypes
	 * @return $this
	 */
	public function setPreserveTypes(bool $preserveTypes = true): self;

	/**
	 * @return PreparedSelect
	 */
	public function prepare(): PreparedSelect;

	/**
	 * @return Traversable<int, array<string, mixed>>
	 */
	public function getIterator(): Traversable;
}
