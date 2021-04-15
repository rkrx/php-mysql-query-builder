<?php

namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\StatementInterface;
use Kir\MySQL\Database;
use Kir\MySQL\Tools\AliasReplacer;

abstract class Statement implements StatementInterface {
	/** @var Database */
	private $db;
	/** @var AliasReplacer */
	private $aliasReplacer;
	/** @var array<string, mixed> */
	private $options;

	/**
	 * @param Database $db
	 * @param array<string, mixed> $options
	 */
	public function __construct($db, array $options = []) {
		$this->db = $db;
		$this->aliasReplacer = new AliasReplacer($db->getAliasRegistry());
		$this->options = $options;
	}

	/**
	 * @param bool $stop
	 * @return $this
	 */
	public function debug($stop = true) {
		if(array_key_exists('debug_formatter', $this->options)) {
			$this->options['debug_formatter']();
		} elseif(PHP_SAPI === 'cli') {
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
	public function cloneStatement(): Statement {
		return clone $this;
	}

	/**
	 * @return AliasReplacer
	 */
	public function aliasReplacer(): AliasReplacer {
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
	abstract public function __toString(): string;
}
