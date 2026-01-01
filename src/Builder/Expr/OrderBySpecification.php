<?php

namespace Kir\MySQL\Builder\Expr;

interface OrderBySpecification {
	/**
	 * Returns an array<int, array{string, string}>, where each value is a [db-expression, sort-direction]
	 * The sort-direction can be either ASC or DESC
	 *
	 * @return array<int, array{string, string&('ASC'|'DESC')}>
	 */
	public function getFields(): array;
}
