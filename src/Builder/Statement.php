<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Database;
use Kir\MySQL\Tools\AliasReplacer;

abstract class Statement {
	/**
	 * @var Database
	 */
	private $db;
	/**
	 * @var AliasReplacer
	 */
	private $aliasReplacer;

	/**
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		$this->db = $db;
		$this->aliasReplacer = new AliasReplacer($db->getAliasRegistry());
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
	 * @return AliasReplacer
	 */
	public function aliasReplacer() {
		return $this->aliasReplacer;
	}

	/**
	 * @return Database
	 */
	protected function db() {
		return $this->db;
	}

	/**
	 * @return string
	 */
	abstract public function __toString();
}
