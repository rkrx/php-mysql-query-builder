<?php

namespace Kir\MySQL\Builder\Internal;

use DateTimeInterface;
use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Builder\Expr\OptionalExpression;
use Kir\MySQL\Builder\Select;
use Kir\MySQL\Tools\VirtualTable;
use Stringable;

/**
 * @phpstan-type DBTableNameType string|array<int, array<string, null|scalar>>|Select|VirtualTable
 * @phpstan-type DBFetchRowsCallableReturnType string|array<int, array<string, null|scalar>>|Select|VirtualTable
 *
 * @phpstan-type DBParameterValueType null|scalar|Stringable|DBExpr|Select|DateTimeInterface|array<null|scalar|Stringable>
 *
 * @phpstan-type DBWhereKeyValueExpressionType array<string, DBParameterValueType>
 * @phpstan-type DBWhereExpressionType string|DBWhereKeyValueExpressionType|object|OptionalExpression
 */
class Types {
}

// (callable(array<string, bool|float|int|string|null>): (array<string, bool|float|int|string|null>|Kir\MySQL\Builder\Helpers\DBIgnoreRow|void|null))|null
// (callable(array<string, bool|float|int|string|null>): (array<K, V>|Kir\MySQL\Builder\Helpers\DBIgnoreRow|void))|null

// array<string, array<string, bool|float|int|string|null>|Kir\MySQL\Builder\Helpers\DBIgnoreRow>
// array<string, bool|float|int|string|null>|Kir\MySQL\Builder\Helpers\DBIgnoreRow
