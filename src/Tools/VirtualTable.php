<?php
namespace Kir\MySQL\Tools;

class VirtualTable {
	/** @var string */
	private $tableName;
	/** @var array<string, mixed> */
	private $params;

	/**
	 * @param string $tableName
	 * @param array<string, mixed> $params
	 */
	public function __construct(string $tableName, array $params = []) {
		$this->tableName = $tableName;
		$this->params = $params;
	}

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
