<?php

namespace Kir\MySQL\Builder\Expr;

class AndExpr extends ConditionGroup {
	/**
	 * @return string
	 */
	protected function getOperator(): string {
		return 'AND';
	}
}
