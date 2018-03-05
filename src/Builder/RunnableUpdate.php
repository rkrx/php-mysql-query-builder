<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DDLPreparable;
use Kir\MySQL\Builder\Traits\CreateDDLRunnable;
use Kir\MySQL\Databases\MySQL;

class RunnableUpdate extends Update implements DDLPreparable {
	use CreateDDLRunnable;
	
	/**
	 * @param MySQL $db
	 * @param array $options
	 */
	public function __construct(MySQL $db, array $options = []) {
		parent::__construct($db, $options);
	}

	/**
	 * @param array $params
	 * @return int
	 */
	public function run(array $params = []) {
		$query = $this->__toString();
		return $this->db()->exec($query, $params);
	}

	/**
	 */
	public function prepare() {
		return $this->createPreparable($this->db()->prepare($this));
	}
}
