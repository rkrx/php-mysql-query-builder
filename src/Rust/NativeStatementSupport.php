<?php

namespace Kir\MySQL\Rust;

use InvalidArgumentException;
use Kir\MySQL\Builder\Internal\ConditionBuilder;
use Kir\MySQL\Builder\InvalidValueException;
use Kir\MySQL\Builder\RunnableTemporaryTable;
use Kir\MySQL\Builder\Value\OptionalValue;
use Kir\MySQL\Common\SpecialTable;
use Kir\MySQL\Database;
use Kir\MySQL\Tools\VirtualTable;
use RuntimeException;

trait NativeStatementSupport {
	abstract protected function db(): Database;

	/**
	 * @return object
	 */
	private function createNativeBuilder(string $shortName) {
		$className = __NAMESPACE__ . "\\Native{$shortName}";
		if(!class_exists($className)) {
			throw new RuntimeException("The native query-builder extension is not loaded; missing {$className}");
		}

		return new $className();
	}

	/**
	 * @param string|null $alias
	 * @param mixed $name
	 * @return string
	 */
	private function buildNativeTableName(?string $alias, $name): string {
		if($name instanceof SpecialTable) {
			$name = $name->asString($this->db());
		} elseif($name instanceof RunnableTemporaryTable) {
			$name = $name->getName();
		} elseif(is_object($name) && !($name instanceof VirtualTable) && method_exists($name, '__toString')) {
			$name = $this->formatNestedQuery((string) $name);
		} elseif(is_array($name)) {
			$name = $this->buildInlineTable($name);
		}
		if((is_string($name) || $name instanceof VirtualTable) && $this->db()->getVirtualTables()->has($name)) {
			$select = (string) $this->db()->getVirtualTables()->get($name);
			$name = sprintf('(%s)', implode("\n\t", explode("\n", trim($select))));
		}
		$name = $this->aliasReplacer()->replace((string) $name);
		if($alias !== null) {
			return sprintf('%s %s', $name, $alias);
		}

		return $name;
	}

	private function formatNestedQuery(string $query): string {
		$query = trim($query);
		$query = rtrim($query, ';');
		$query = trim($query);
		$lines = explode("\n", $query);
		$lines = array_map(static fn(string $line): string => "\t{$line}", $lines);

		return '(' . trim(implode("\n", $lines)) . ')';
	}

	private function formatFieldSubquery(string $query): string {
		$query = trim($query);
		$query = rtrim($query, ';');
		$query = trim($query);
		$lines = explode("\n", $query);
		$lines = array_map(static fn(string $line): string => "\t\t{$line}", $lines);

		return sprintf("(\n%s\n\t)", implode("\n", $lines));
	}

	/**
	 * @param array<int, mixed> $rows
	 * @return string
	 */
	private function buildInlineTable(array $rows): string {
		$parts = [];
		foreach($rows as $bucket) {
			if(is_scalar($bucket)) {
				$parts[] = sprintf(
					'SELECT %s AS %s',
					$this->db()->quote($bucket),
					$this->db()->quoteField('value')
				);
			} elseif(is_iterable($bucket)) {
				$values = [];
				foreach($bucket as $field => $value) {
					$values[] = sprintf(
						'%s AS %s',
						$this->db()->quote($value),
						$this->db()->quoteField((string) $field)
					);
				}
				$parts[] = sprintf('SELECT %s', implode(', ', $values));
			} else {
				throw new InvalidArgumentException('Only scalar values and iterables are supported as table data');
			}
		}

		return '(' . implode("\n\tUNION ALL\n\t", $parts) . ')';
	}

	/**
	 * @param mixed $expression
	 * @param array<int, mixed> $arguments
	 * @return list<string>
	 */
	private function buildNativeConditionLines($expression, array $arguments): array {
		$query = ConditionBuilder::build($this->db(), '', [[$expression, array_values($arguments)]], '__NATIVE__');
		if($query === '') {
			return [];
		}

		$prefix = "__NATIVE__\n";
		if(strncmp($query, $prefix, strlen($prefix)) !== 0) {
			return [];
		}
		$query = rtrim(substr($query, strlen($prefix)), "\n");
		if($query === '') {
			return [];
		}

		return array_map(static fn(string $line): string => preg_replace('{^\t}', '', $line), explode("\n\tAND\n", $query));
	}

	/**
	 * @param mixed $value
	 * @return string|null
	 */
	private function normalizeNativeLimitOffset($value): ?string {
		if($value instanceof OptionalValue) {
			if(!$value->isValid()) {
				return null;
			}
			$value = $value->getValue();
			if($value === null || is_scalar($value)) {
				$value = (string) $value;
			} else {
				throw new InvalidValueException('Value for OFFSET has to be a number');
			}
			if(!preg_match('{^\d+$}', $value)) {
				throw new InvalidValueException('Value for OFFSET has to be a number');
			}

			return $value;
		}
		if($value === null) {
			return null;
		}
		if(!is_int($value)) {
			if(!is_numeric($value)) {
				throw new InvalidValueException('Value for OFFSET has to be a number');
			}
			$value = (int) $value;
		}

		return (string) $value;
	}

	/**
	 * @param string|array<int, mixed> $expression
	 */
	private function normalizeNativeExpression($expression): ?string {
		if(is_array($expression)) {
			if(count($expression) < 1) {
				return null;
			}

			return $this->db()->quoteExpression((string) $expression[0], array_slice($expression, 1));
		}

		return (string) $expression;
	}

	private function normalizeNativeDirection(string $direction): string {
		return strtoupper($direction) !== 'ASC' ? 'DESC' : 'ASC';
	}
}
