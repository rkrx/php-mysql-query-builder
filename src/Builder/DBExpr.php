<?php

namespace Kir\MySQL\Builder;

class DBExpr {
	/** @var string */
	private $expression;

	/**
	 * @param $expression
	 */
	public function __construct($expression) {
		$this->expression = $expression;
	}

	/**
	 * @return string
	 */
	public function getExpression() {
		return $this->expression;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->expression;
	}
}
