<?php
namespace Kir\MySQL\Builder\Internal;

use Kir\MySQL\Database\DatabaseStatement;

/**
 * @template T
 */
class DDLRunnable {
	/** @var DatabaseStatement */
	private $query;
	/** @var null|callable(scalar=): T */
	private $callbackFn;

	/**
	 * @param DatabaseStatement $query
	 * @param null|callable(scalar=): T $callbackFn
	 */
	public function __construct(DatabaseStatement $query, ?callable $callbackFn = null) {
		$this->query = $query;
		$this->callbackFn = $callbackFn;
	}

	/**
	 * @param array<string, mixed> $params
	 * @return T|int
	 */
	public function run(array $params = []) {
		$this->query->execute($params);
		$response = $this->query->getStatement()->rowCount();
		if($this->callbackFn !== null) {
			$response = call_user_func($this->callbackFn, $response);
		}
		return $response;
	}
}
