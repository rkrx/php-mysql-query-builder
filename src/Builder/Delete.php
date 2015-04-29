<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Traits\JoinBuilder;
use Kir\MySQL\Builder\Traits\LimitBuilder;
use Kir\MySQL\Builder\Traits\OffsetBuilder;
use Kir\MySQL\Builder\Traits\OrderByBuilder;
use Kir\MySQL\Builder\Traits\TableBuilder;
use Kir\MySQL\Builder\Traits\TableNameBuilder;
use Kir\MySQL\Builder\Traits\WhereBuilder;

class Delete extends Statement {
	use TableNameBuilder;
	use TableBuilder;
	use JoinBuilder;
	use WhereBuilder;
	use OrderByBuilder;
	use LimitBuilder;
	use OffsetBuilder;

	/**
	 * @var string[]
	 */
	private $aliases = array();

	/**
	 * Name der Tabelle
	 *
	 * @param string $alias
	 * @param string $table
	 * @return $this
	 */
	public function from($alias, $table = null) {
		if($table === null) {
			list($alias, $table) = [$table, $alias];
		} else {
			$this->aliases[] = $alias;
		}
		$this->addTable($alias, $table);
		return $this;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function __toString() {
		$query = "DELETE ";
		$query .= join(', ', $this->aliases);
		$query = trim($query) . " FROM\n";
		$query = $this->buildTables($query);
		$query = $this->buildJoins($query);
		$query = $this->buildWhereConditions($query);
		$query = $this->buildOrder($query);
		$query = $this->buildLimit($query);
		$query = $this->buildOffset($query);
		return $query;
	}
}
