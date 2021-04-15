<?php
namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Select;
use Kir\MySQL\Builder;
use Kir\MySQL\Tools\VirtualTable;

trait JoinBuilder {
	use AbstractDB;
	use AbstractTableNameBuilder;

	/** @var array<int, array{type: string, alias: string, name: string|array<int, array<string, mixed>>|Select|VirtualTable, expression: string|null, arguments: array<int, null|string|array<int, string>|Builder\DBExpr|Builder\Select>}> */
	private $joinTables = [];

	/**
	 * @param string $alias
	 * @param string|array<int, array<string, mixed>>|Select|VirtualTable $table
	 * @param string|null $expression
	 * @param null|int|float|string|array<int, string>|Builder\DBExpr|Builder\Select ...$args
	 * @return $this
	 */
	public function joinInner(string $alias, $table, ?string $expression = null, ...$args): self {
		return $this->addJoin('INNER', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $alias
	 * @param string|array<int, array<string, mixed>>|Select|VirtualTable $table
	 * @param string $expression
	 * @param string|int|float|array<int, string>|Builder\DBExpr|Builder\Select ...$args
	 * @return $this
	 */
	public function joinLeft(string $alias, $table, string $expression, ...$args): self {
		return $this->addJoin('LEFT', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $alias
	 * @param string|array<int, array<string, mixed>>|Select|VirtualTable $table
	 * @param string $expression
	 * @param string|int|float|array<int, string>|Builder\DBExpr|Builder\Select ...$args
	 * @return $this
	 */
	public function joinRight(string $alias, $table, string $expression, ...$args): self {
		return $this->addJoin('RIGHT', $alias, $table, $expression, $args);
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildJoins(string $query): string {
		$arr = [];
		foreach($this->joinTables as $table) {
			$join = $table['type']." JOIN\n";
			$join .= "\t" . $this->buildTableName($table['alias'], $table['name']);
			if($table['expression']) {
				$join .= " ON " . $this->db()->quoteExpression($table['expression'], $table['arguments']);
			}
			$arr[] = $join;
		}
		if(count($arr)) {
			$query .= implode("\n", $arr)."\n";
		}
		return $query;
	}

	/**
	 * @param string $type
	 * @param string $alias
	 * @param string|array<int, array<string, mixed>>|Select|VirtualTable $name
	 * @param string|null $expression
	 * @param array<int, mixed> $arguments
	 * @return $this
	 */
	private function addJoin(string $type, string $alias, $name, ?string $expression = null, array $arguments = []) {
		$this->joinTables[] = [
			'type' => $type,
			'alias' => $alias,
			'name' => $name,
			'expression' => $expression,
			'arguments' => $arguments
		];
		return $this;
	}
}
