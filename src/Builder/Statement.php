<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Databases\MySQL;
use Kir\MySQL\Tools\AliasReplacer;

abstract class Statement {
	/** @var MySQL */
	private $db;
	/** @var AliasReplacer */
	private $aliasReplacer;
	/** @var array */
	private $options;

	/**
	 * @param MySQL $db
	 * @param array $options
	 */
	public function __construct(MySQL $db, array $options = []) {
		$this->db = $db;
		$this->aliasReplacer = new AliasReplacer($db->getAliasRegistry());
		$this->options = $options;
	}

	/**
	 * @param bool $stop
	 * @return $this
	 */
	public function debug($stop = true) {
		if (PHP_SAPI === 'cli') {
			echo "\n{$this->__toString()}\n";
		} else {
			echo "<pre>{$this->__toString()}</pre>";
		}
		if ($stop) {
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
	 * @return MySQL
	 */
	protected function db(): MySQL {
		return $this->db;
	}

	/**
	 * @return string
	 */
	abstract public function __toString(): string;
}
