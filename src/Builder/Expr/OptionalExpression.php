<?php
namespace Kir\MySQL\Builder\Expr;

interface OptionalExpression {
	/**
	 * @return string
	 */
	public function getExpression();

	/**
	 * @return bool
	 */
	public function isValid();

	/**
	 * @return mixed
	 */
	public function getData();
}
