<?php

namespace Kir\MySQL\Databases\MySQL;

use Kir\MySQL\Builder\RunnableTemporaryTable;
use Kir\MySQL\Database;

class MySQLTemporaryTable implements RunnableTemporaryTable {
	public function __construct(
		private Database $db,
		private string $name
	) {}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return $this
	 */
	public function release() {
		$this->db->exec("DROP TEMPORARY TABLE IF EXISTS {$this->name}");

		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->name;
	}
}
