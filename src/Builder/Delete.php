<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\Types;
use Kir\MySQL\Builder\Traits\JoinBuilder;
use Kir\MySQL\Builder\Traits\LimitBuilder;
use Kir\MySQL\Builder\Traits\OffsetBuilder;
use Kir\MySQL\Builder\Traits\OrderByBuilder;
use Kir\MySQL\Builder\Traits\TableBuilder;
use Kir\MySQL\Builder\Traits\TableNameBuilder;
use Kir\MySQL\Builder\Traits\WhereBuilder;

/**
 * @phpstan-import-type DBTableNameType from Types
 */
abstract class Delete extends Statement {
	use TableNameBuilder;
	use TableBuilder;
	use JoinBuilder;
	use WhereBuilder;
	use OrderByBuilder;
	use LimitBuilder;
	use OffsetBuilder;

	/** @var string[] */
	private array $aliases = [];

	/**
	 * Name der Tabelle
	 *
	 * @param ($table is null ? DBTableNameType : string) $alias
	 * @param null|DBTableNameType $table
	 * @return $this
	 */
	public function from($alias, $table = null) {
		if($table !== null) {
			$this->aliases[] = $alias;
		}
		if($table === null) {
			[$alias, $table] = [$table, $alias];
		}
		$this->addTable($alias, $table);
		return $this;
	}

	/**
	 * @param array<string, mixed> $params
	 * @return int
	 */
	abstract public function run(array $params = []);

	/**
	 * @return string
	 */
	public function __toString(): string {
		$query = 'DELETE ';
		$query .= implode(', ', $this->aliases);
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
