<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Tools\AliasReplacer;

class Delete extends Statement {
	/**
	 * @var string
	 */
	private $table = null;

	/**
	 * @var array
	 */
	private $where = array();

	/**
	 * Name der Tabelle
	 *
	 * @param string $name
	 * @return $this

	 */
	public function from($name) {
		$this->table = $name;
		return $this;
	}

	/**
	 * @param string $expr
	 * @return $this
	 */
	public function where($expr) {
		$arguments = array_slice(func_get_args(), 1);
		$expr = $this->db()->quoteExpression($expr, $arguments);
		$this->where[] = "({$expr})";
		return $this;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function __toString() {
		if ($this->table === null) {
			throw new Exception('Specify a table-name');
		}

		$sqlTable = (new AliasReplacer($this->db()->getAliasRegistry()))->replace($this->table);
		$queryArr = array();
		$queryArr[] = "DELETE "."FROM\n\t{$sqlTable}\n";

		if (!empty($this->where)) {
			$sqlWhere = join("\n\tAND\n\t", $this->where);
			$queryArr[] = "WHERE\n\t{$sqlWhere}\n";
		}
		$queryArr[] = ";";

		return join('', $queryArr);
	}
}