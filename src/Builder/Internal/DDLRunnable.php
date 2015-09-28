<?php
namespace Kir\MySQL\Builder\Internal;

use Kir\MySQL\Database\DatabaseStatement;

class DDLRunnable {
	/** @var DatabaseStatement */
	private $query;
	/** @var callable */
	private $callbackFn;

	/**
	 * @param DatabaseStatement $query
	 * @param callable $callbackFn
	 */
	public function __construct(DatabaseStatement $query, callable $callbackFn = null) {
		$this->query = $query;
		$this->callbackFn = $callbackFn;
	}

	/**
	 * @param array $params
	 * @return mixed
	 */
	public function run(array $params = array()) {
		$this->query->execute($params);
		$response = $this->query->getStatement()->rowCount();
		if($this->callbackFn !== null) {
			$response = call_user_func($this->callbackFn, $response);
		}
		return $response;
	}
}
