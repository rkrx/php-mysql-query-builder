<?php
namespace Kir\MySQL\Builder;

class DBExpr {
	/**
	 * @param string $expression
	 */
	public function __construct(
		private string $expression
	) {}

	/**
	 * @return string
	 */
	public function getExpression(): string {
		return $this->expression;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->expression;
	}
}
