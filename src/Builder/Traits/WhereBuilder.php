<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Helpers\ConditionAddHelper;
use Kir\MySQL\Builder\Internal\ConditionBuilder;
use Kir\MySQL\Builder\Internal\Types;
use Stringable;

/**
 * @phpstan-import-type DBParameterValueType from Types
 * @phpstan-import-type DBWhereExpressionType from Types
 */
trait WhereBuilder {
	use AbstractDB;

	/** @var array<int, array{DBWhereExpressionType, DBParameterValueType[]}> */
	private array $where = [];

	/**
	 * @param DBWhereExpressionType $expression
	 * @param DBParameterValueType ...$args
	 * @return $this
	 */
	public function where($expression, ...$args) {
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
