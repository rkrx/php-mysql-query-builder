<?php
namespace Kir\MySQL\Builder\Internal;

use Kir\MySQL\Database;

interface StatementInterface {
	/**
	 * @param Database $db
	 * @param array<string, mixed> $options
	 */
	public function __construct(Database $db, array $options = []);
}
