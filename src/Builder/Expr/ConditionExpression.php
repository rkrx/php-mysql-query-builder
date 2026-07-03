<?php

namespace Kir\MySQL\Builder\Expr;

use Kir\MySQL\Database;

interface ConditionExpression {
	/**
	 * @param Database $db
	 * @return string|null
	 */
	public function buildCondition(Database $db): ?string;
}
