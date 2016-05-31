<?php
namespace Kir\MySQL\Builder\Traits;

use Closure;

trait ExtensionMethodBuilder {
	use AbstractDB;

	/** @var array */
	private $where = array();

	/**
	 * @param string $name
	 * @param mixed ...$arguments
	 * @return $this|mixed
	 */
	public function ext($name, $arguments) {
		return $this->__call($name, array_slice(func_get_args(), 1));
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return $this|mixed
	 * @throws \Exception
	 */
	public function __call($name, array $arguments) {
		$factory = $this->db()->getExtensionMethodRegistry()->get($name);
		if(is_string($factory)) {
			$callable = new $factory($this);
		} elseif($factory instanceof Closure || method_exists($factory, '__invoke')) {
			$callable = call_user_func($factory, $this);
		} else {
			throw new \Exception('Invalid extension-method-factory');
		}
		$result = call_user_func_array($callable, $arguments);
		if($result !== null) {
			return $result;
		}
		return $this;
	}
}
