<?php
namespace Kir\MySQL\Builder;

class DBExpr {
	/** @var string */
	private $expression;

	/**
	 * @param string $expression
	 */
	public function __construct(string $expression) {
		$this->expression = $expression;
	}

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
