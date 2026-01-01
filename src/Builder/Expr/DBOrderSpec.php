<?php

namespace Kir\MySQL\Builder\Expr;

/**
 * Defines fields that are sortable. Sortable fields have an alias.
 * The alias can be passed as a sort specifier - along with the direction in which to sort.
 */
class DBOrderSpec implements DBOrderSpecInterface, OrderBySpecification {
	/** @var array<string, string> */
	private $sortSpecification;
	/** @var array<string, string> */
	private $sortingInstruction;
	/** @var array<string, mixed> */
	private $options;

	/**
	 * @param array<string, string> $sortSpecification
	 * @param array<string, string> $sortingInstruction
	 * @param array{max_sort_instructions?: positive-int} $options
	 * @return static
	 */
	public static function from(array $sortSpecification, array $sortingInstruction, array $options = []) {
		return new static($sortSpecification, $sortingInstruction, $options);
	}

	/**
	 * @inheritDoc
	 */
	public function __construct(array $sortSpecification, array $sortingInstruction, array $options = []) {
		$this->sortSpecification = $sortSpecification;
		$this->sortingInstruction = $sortingInstruction;
		$this->options = $options;
	}

	/**
	 * @return array<int, array{string, string&('ASC'|'DESC')}>
	 */
	public function getFields(): array {
		$fields = [];
		$max = $this->options['max_sort_instructions'] ?? 16;
		foreach($this->sortingInstruction as $alias => $direction) {
			$direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
			if(!array_key_exists($alias, $this->sortSpecification)) {
				throw new DBSortAliasNotFoundException('Sorting: Alias for DB-Expression not found');
			}
			$fields[] = [$this->sortSpecification[$alias], $direction];
			if($max < 1) {
				throw new DBSortTooManyInstructionsException();
			}
			$max--;
		}

		return $fields;
	}
}
