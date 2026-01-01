<?php

namespace Kir\MySQL\Tools;

use Kir\MySQL\Common\SpecialTable;
use Kir\MySQL\Database;

/**
 * @phpstan-type TColumn string|array{name: string, type: string, jsonPath: string}
 *
 * @phpstan-type TColumns list<string|TColumn>
 */
class JsonTable implements SpecialTable {
	/**
	 * @param string $dataExpression The field to get the source json data from
	 * @param string $jsonPath The json path to the array to be turned into a table
	 * @param TColumns $columns The projected columns
	 */
	public function __construct(
		private string $dataExpression,
		private string $jsonPath,
		private $columns,
	) {}

	public function asString(Database $db): string {
		return sprintf(
			'JSON_TABLE(%s, %s, %s)',
			$this->dataExpression,
			$this->jsonPath,
			$this->translateColumns($db, $this->columns)
		);
	}

	/**
	 * @param Database $db
	 * @param TColumns $columns
	 * @return string
	 */
	private function translateColumns(Database $db, $columns) {
		$result = [];
		foreach($columns as $column) {
			if(!is_string($column)) {
				$column = sprintf('%s %s PATH \'%s\'', $column['name'], $column['type'], $column['jsonPath']);
			}
			$result[] = $column;
		}

		return sprintf('COLUMNS(%s)', implode(', ', $result));
	}
}
