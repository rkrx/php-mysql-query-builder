<?php
namespace Kir\MySQL\Builder\Expr;

use Exception;

class OptionalDBFilterMap implements OptionalExpression {
	/** @var */
	private $expression;
	/** @var mixed */
	private $data;
	/** @var callable|null */
	private $validator;

	/**
	 * @param string $expression
	 * @param array $data
	 * @param string|string[] $keyPath
	 * @param callable|null $validator
	 */
	public function __construct($expression, array $data, $keyPath, $validator = null) {
		$this->expression = $expression;
		$this->data = $data;
		$keyPath = $this->buildKey($keyPath);
		$this->data = $this->recursiveGet($data, $keyPath, null);
		if($validator === null) {
			$validator = function ($data) {
				return (string) $data !== '';
			};
		}
		$this->validator = $validator;
	}

	/**
	 * @return mixed
	 */
	public function getExpression() {
		return $this->expression;
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return call_user_func($this->validator, $this->data);
	}

	/**
	 * @return mixed
	 */
	public function getData() {
		return [$this->data];
	}

	/**
	 * @param string|string[] $keyPath
	 * @return string
	 * @throws Exception
	 */
	private function buildKey($keyPath) {
		if(is_string($keyPath)) {
			$keyPath = explode('.', $keyPath);
		}
		if(!is_array($keyPath)) {
			throw new Exception('Invalid key');
		}
		return $keyPath;
	}

	/**
	 * @param array $array
	 * @param array $path
	 * @param mixed $default
	 * @return array
	 */
	private function recursiveGet($array, $path, $default) {
		$count = count($path);
		if (!$count) {
			return $default;
		}
		for($idx = 0; $idx < $count; $idx++) {
			$part = $path[$idx];
			if(!array_key_exists($part, $array)) {
				return $default;
			}
			$array = $array[$part];
		}
		return $array;
	}
}
