<?php

namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Builder\Select;

trait UnionBuilder {
	use AbstractDB;

	/** @var array<int, array{''|'ALL', string|Select}> */
	private array $unions = [];

	/**
	 * @param string|Select ...$queries
	 * @return $this
	 */
	public function union(...$queries) {
		foreach($queries as $query) {
			$this->unions[] = ['', $query];
		}

		return $this;
	}

	/**
	 * @param string|Select ...$queries
	 * @return $this
	 */
	public function unionAll(...$queries) {
		foreach($queries as $query) {
			$this->unions[] = ['ALL', $query];
		}

		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildUnions(string $query): string {
		$wrap = static function($query) {
			$query = trim($query);
			$query = implode("\n\t", explode("\n", $query));

			return sprintf("(\n\t%s\n)", $query);
		};
		$queries = [$wrap($query)];
		foreach($this->unions as $unionQuery) {
			if($unionQuery[0] === 'ALL') {
				$queries[] = 'UNION ALL';
			} else {
				$queries[] = 'UNION';
			}
			$queries[] = $wrap($unionQuery[1]);
		}
		if(count($queries) > 1) {
			return implode(' ', $queries);
		}

		return $query;
	}
}
