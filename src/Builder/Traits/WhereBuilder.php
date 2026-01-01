<?php

namespace Kir\MySQL\Builder\Traits;

use Closure;
use Kir\MySQL\Builder\Helpers\ConditionAddHelper;
use Kir\MySQL\Builder\Internal\ConditionBuilder;
use Kir\MySQL\Builder\Internal\Types;

/**
 * @phpstan-import-type DBParameterValueType from Types
 * @phpstan-import-type DBWhereExpressionType from Types
 */
trait WhereBuilder {
	use AbstractDB;

	/** @var array<int, array{DBWhereExpressionType, list<DBParameterValueType>}> */
	private array $where = [];

	/**
	 * @param DBWhereExpressionType $expression
	 * @param DBParameterValueType ...$args
	 * @return $this
	 */
	public function where($expression, ...$args) {
		/** @var Closure(DBWhereExpressionType, list<DBParameterValueType>):void $fn */
		$fn = fn($expression, $args) => $this->where[] = [$expression, $args];
		ConditionAddHelper::addCondition($fn, $expression, $args);

		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildWhereConditions(string $query): string {
		return ConditionBuilder::build($this->db(), $query, $this->where, 'WHERE');
	}
}
