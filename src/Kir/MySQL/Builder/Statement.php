<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\MySQL;

abstract class Statement {
	/**
	 * @var
	 */
	private $mysql;

	/**
	 * @param MySQL $mysql
	 */
	public function __construct(MySQL $mysql) {
		$this->mysql = $mysql;
	}

	/**
	 * @return MySQL
	 */
	protected function mysql() {
		return $this->mysql;
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
}