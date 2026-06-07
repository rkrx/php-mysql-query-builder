<?php

namespace Kir\MySQL\Databases\Mock;

use Kir\MySQL\Builder\QueryStatement;
use Kir\MySQL\Databases\MySQL\MySQLExceptionInterpreter;
use Kir\MySQL\QueryLogger\QueryLoggers;

class MockQueryStatement extends QueryStatement {
	private string $query;

	public function __construct(MockPDOStatement $statement, string $query) {
		$this->query = $query;
		parent::__construct(
			$statement,
			$query,
			new MySQLExceptionInterpreter(),
			new QueryLoggers()
		);
	}

	public function __toString(): string {
		return $this->query;
	}
}
