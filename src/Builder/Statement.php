<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Database;

abstract class Statement {
	/**
	 * @var
	 */
	private $db;

	/**
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		$this->db = $db;
	}

	/**
	 * @param bool $stop
	 * @return $this
	 */
	public function debug($stop = true) {
		if(php_sapi_name() == 'cli') {
			echo "\n{$this->__toString()}\n";
		} else {
			echo "<pre>{$this->__toString()}</pre>";
		}
		if($stop) {
			exit;
		}
		return $this;
	}

	/**
	 * @return Statement
	 */
	public function cloneStatement() {
		return clone $this;
	}

	/**
	 * @return Database
	 */
	protected function db() {
		return $this->db;
	}
}
