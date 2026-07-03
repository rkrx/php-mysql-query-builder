<?php

namespace Kir\MySQL\Builder\Expr;

use Kir\MySQL\Builder\Internal\ConditionBuilder;
use Kir\MySQL\Builder\Internal\Types;
use Kir\MySQL\Database;

/**
 * @phpstan-import-type DBWhereExpressionType from Types
 */
abstract class ConditionGroup implements ConditionExpression {
	/** @var list<DBWhereExpressionType> */
	private array $expressions;

	/**
	 * @param DBWhereExpressionType ...$expressions
	 */
	final public function __construct(...$expressions) {
		$this->expressions = array_values($expressions);
	}

	/**
	 * @param Database $db
	 * @return string|null
	 */
	public function buildCondition(Database $db): ?string {
		$parts = [];
		foreach($this->expressions as $expression) {
			$condition = ConditionBuilder::buildExpression($db, $expression);
			if($condition !== null) {
				$parts[] = $condition;
			}
		}

		if(count($parts) < 1) {
			return null;
		}
		if(count($parts) === 1) {
			return $parts[0];
		}

		return implode(" {$this->getOperator()} ", array_map(static fn(string $condition): string => "({$condition})", $parts));
	}

	/**
	 * @return string
	 */
	abstract protected function getOperator(): string;
}
