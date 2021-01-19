<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Tools\AliasReplacer;

trait AbstractAliasReplacer {
	/**
	 * @return AliasReplacer
	 */
	abstract protected function aliasReplacer(): AliasReplacer;
}
