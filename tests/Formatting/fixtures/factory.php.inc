<?php

namespace FormattingFixture;

use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Database;

final class ExternalFactory {
	public static function create(Database $db): RunnableSelect {
		return $db->select();
	}
}
