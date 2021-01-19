<?php
namespace Kir\MySQL\Builder\Expr;

interface OptionalExpression {
	/**
	 * @return string
	 */
	public function getExpression(): string;

	/**
	 * @return bool
	 */
	public function isValid(): bool;

	/**
	 * @return mixed
	 */
	public function getValue();
}
