<?php
namespace Kir\MySQL\Builder;

use Closure;
use PDO;
use PDOStatement;

class RunnableSelect extends Select {
	/**
	 * @param Closure $callback
	 * @return array[]
	 */
	public function fetchRows(Closure $callback = null) {
		$statement = $this->createStatement();
		$data = $statement->fetchAll(PDO::FETCH_ASSOC);
		if($callback !== null) {
			$data = array_map($callback, $data);
		}
		$statement->closeCursor();
		return $data;
	}

	/**
	 * @return string[]
	 */
	public function fetchRow() {
		$statement = $this->createStatement();
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		if(!is_array($row)) {
			return array();
		}
		return $row;
	}

	/**
	 * @return string[]
	 */
	public function fetchArray() {
		return $this->fetchRows(function ($row) {
			reset($row);
			return current($row);
		});
	}

	/**
	 * @param mixed $default
	 * @return string[]
	 */
	public function fetchValue($default = null) {
		$statement = $this->createStatement();
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		if(!is_array($row)) {
			return $default;
		}
		if(!count($row)) {
			return null;
		}
		return array_shift($row);
	}

	/**
	 * @return PDOStatement
	 */
	private function createStatement() {
		return $this->mysql()->query($this->__toString());
	}
}