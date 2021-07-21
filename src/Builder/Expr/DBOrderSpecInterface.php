<?php

namespace Kir\MySQL\Builder\Expr;

/**
 * This interface is needed to allow new static in the deriving class
 */
interface DBOrderSpecInterface {
	/**
	 * @param array<string, string> $sortSpecification Key = Alias, Value = DB-Expression
	 * @param array<string, string> $sortingInstruction Key = Alias, Value = Sortdirection ("ASC" | "DESC")
	 * @param array{max_sort_instructions?: positive-int} $options
	 */
	public function __construct(array $sortSpecification, array $sortingInstruction, array $options = []);
}
