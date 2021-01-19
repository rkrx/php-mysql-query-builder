<?php
namespace Kir\MySQL\Builder\Expr;

interface OrderBySpecification {
	/**
	 * Returns an array[], where each value is a [db-expression, sort-direction]
	 * The sort-direction can be either ASC or DESC
	 *
	 * @return array[]
	 */
	public function getFields(): array;
}
