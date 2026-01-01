<?php

namespace Kir\MySQL\Tools;

class VirtualTable {
	/**
	 * @param string $tableName
	 * @param array<string, mixed> $params
	 */
	public function __construct(
		private string $tableName,
		private array $params = [],
	) {}

	/**
	 * @return string
	 */
	public function getTableName(): string {
		return $this->tableName;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getParams(): array {
		return $this->params;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->getTableName();
	}
}
