<?php

namespace Kir\MySQL\Builder\Traits;

use Kir\MySQL\Database;

trait AbstractDB {
	/**
	 * @return Database
	 */
	abstract protected function db(): Database;
}
