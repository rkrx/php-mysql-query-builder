<?php
namespace Kir\MySQL\Builder;

use BadMethodCallException;
use Kir\MySQL\Builder\Internal\DDLPreparable;
use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Builder\Traits\CreateDDLRunnable;
use Kir\MySQL\Databases\MySQL;
use Traversable;

class RunnableInsert extends Insert implements DDLPreparable {
	use CreateDDLRunnable;
	
	/**
	 * @param MySQL $db
	 * @param array $options
	 */
	public function __construct(MySQL $db, array $options = []) {
		parent::__construct($db, $options);
	}

	/**
	 * @param array|Traversable|mixed $rows
	 * @return int[] Insert IDs
	 */
	public function insertRows($rows) {
		if (!(is_array($rows) || $rows instanceof Traversable)) {
			throw new BadMethodCallException('Expected $rows to by an array or an instance of \\Traversable');
		}
		$result = [];
		$query = $this->__toString();
		$stmt = $this->db()->prepare($query);
		foreach ($rows as $row) {
			$stmt->execute($row);
			$result[] = (int) $this->db()->getLastInsertId();
		}
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @return DDLRunnable
	 */
	public function prepare() {
		return $this->createPreparable($this->db()->prepare($this), function() {
			return (int) $this->db()->getLastInsertId();
		});
	}

	/**
	 * @param array $params
	 * @return int
	 */
	public function run(array $params = []) {
		return $this->prepare()->run($params);
	}
}
