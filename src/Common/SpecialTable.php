<?php

namespace Kir\MySQL\Common;

use Kir\MySQL\Database;

interface SpecialTable {
	public function asString(Database $db): string;
}
