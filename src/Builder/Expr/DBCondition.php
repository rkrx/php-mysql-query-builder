<?php

namespace Kir\MySQL\Builder\Expr;

use Kir\MySQL\Builder\Internal\Types;
use Kir\MySQL\Database;

/**
 * @phpstan-import-type DBParameterValueType from Types
 */
class DBCondition implements ConditionExpression {
	/** @var list<DBParameterValueType> */
	private array $arguments;

	/**
	 * @param string $expression
	 * @param DBParameterValueType ...$arguments
	 */
	public function __construct(
		private string $expression,
		...$arguments,
	) {
		$this->arguments = array_values($arguments);
	}

	/**
	 * @param Database $db
	 * @return string
	 */
	public function buildCondition(Database $db): string {
		return $db->quoteExpression($this->expression, $this->arguments);
	}
}
