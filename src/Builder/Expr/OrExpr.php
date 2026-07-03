<?php

namespace Kir\MySQL\Builder\Expr;

class OrExpr extends ConditionGroup {
	/**
	 * @return string
	 */
	protected function getOperator(): string {
		return 'OR';
	}
}
