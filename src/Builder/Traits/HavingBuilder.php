<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Helpers\ConditionAddHelper;
use Kir\MySQL\Builder\Internal\ConditionBuilder;
use Kir\MySQL\Builder\Internal\Types;

/**
 * @phpstan-import-type DBParameterValueType from Types
 * @phpstan-import-type DBWhereExpressionType from Types
 */
trait HavingBuilder {
	use AbstractDB;

	/** @var array<int, array{DBWhereExpressionType, array<DBParameterValueType>}> */
	private array $having = [];

	/**
	 * @param DBWhereExpressionType $expression
	 * @param DBParameterValueType ...$args
	 * @return $this
	 */
	public function having($expression, ...$args) {
		$fn = fn($expression, $args) => $this->having[] = [$expression, $args];
		ConditionAddHelper::addCondition($fn, $expression, $args);
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildHavingConditions(string $query): string {
		return ConditionBuilder::build($this->db(), $query, $this->having, 'HAVING');
	}
}
