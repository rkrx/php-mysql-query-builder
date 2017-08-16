<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Internal\DDLPreparable;
use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Builder\Traits\CreateDDLRunnable;
use Kir\MySQL\Databases\MySQL;

class RunnableDelete extends Delete implements DDLPreparable {
	use CreateDDLRunnable;
	
	/**
	 * @param MySQL $db
	 * @param array $options
	 */
	public function __construct(MySQL $db, array $options = []) {
		parent::__construct($db);
	}

	/**
	 * @param array $params
	 * @return int
	 */
	public function run(array $params = array()) {
		return $this->prepare()->run($params);
	}

	/**
	 * @return DDLRunnable
	 */
	public function prepare() {
		return $this->createPreparable($this->db()->prepare($this));
	}
}
