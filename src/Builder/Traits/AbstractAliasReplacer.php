<?php

namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Tools\AliasReplacer;

trait AbstractAliasReplacer {
	/**
	 * @return AliasReplacer
	 */
	abstract public function aliasReplacer(): AliasReplacer;
}
