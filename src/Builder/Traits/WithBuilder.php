<?php

namespace Kir\MySQL\Builder\Traits;

use InvalidArgumentException;
use Kir\MySQL\Builder\Select;
use LogicException;

trait WithBuilder {
	/** @var array<string, array{statement: string, columns: array<int, string>}> */
	private array $commonTableExpressions = [];
	private bool $recursiveCommonTableExpressions = false;

	/**
	 * @param string $name
	 * @param string|Select $select
	 * @param array<int, string> $columns
	 * @return $this
	 */
	public function with(string $name, $select, array $columns = []) {
		if($select === $this) {
			throw new LogicException('A select statement cannot use itself as a common table expression.');
		}

		$name = trim($name);
		if($name === '') {
			throw new InvalidArgumentException('A common table expression requires a name.');
		}
		if(array_key_exists($name, $this->commonTableExpressions)) {
			throw new LogicException("The common table expression '{$name}' is already defined.");
		}

		$statement = trim((string) $select);
		$statement = trim(rtrim($statement, ';'));
		$this->commonTableExpressions[$name] = [
			'statement' => $statement,
			'columns' => array_values($columns),
		];

		return $this;
	}

	/**
	 * @param string $name
	 * @param string|Select $select
	 * @param array<int, string> $columns
	 * @return $this
	 */
	public function withRecursive(string $name, $select, array $columns = []) {
		$this->with($name, $select, $columns);
		$this->recursiveCommonTableExpressions = true;

		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildWith(string $query): string {
		if(count($this->commonTableExpressions) === 0) {
			return $query;
		}

		$expressions = [];
		foreach($this->commonTableExpressions as $name => $expression) {
			$columns = '';
			if(count($expression['columns']) > 0) {
				$columns = sprintf(' (%s)', implode(', ', $expression['columns']));
			}

			$statement = implode("\n\t\t", explode("\n", $expression['statement']));
			$expressions[] = sprintf("\t%s%s AS (\n\t\t%s\n\t)", $name, $columns, $statement);
		}

		$recursive = $this->recursiveCommonTableExpressions ? ' RECURSIVE' : '';

		return sprintf("WITH%s\n%s\n%s", $recursive, implode(",\n", $expressions), $query);
	}
}
