<?php
namespace Kir\MySQL\Builder\Extensions;

use Kir\MySQL\Builder\Helpers\RecursiveArray;
use Kir\MySQL\Builder\Traits\WhereBuilder;

class CondWhere {
	/** @var WhereBuilder */
	private $whereBuilder;
	/** @var callable */
	private $validator;

	/**
	 * @param WhereBuilder $whereBuilder
	 * @param null $validator
	 */
	public function __construct($whereBuilder, $validator = null) {
		$this->whereBuilder = $whereBuilder;
		if($validator === null) {
			$validator = function ($value) {
				if(!is_scalar($value) && !is_null($value)) {
					return false;
				}
				if($value === '' || $value === null) {
					return false;
				}
				return true;
			};
		}
		$this->validator = $validator;
	}

	/**
	 * @param string $expression
	 * @param array $arr
	 * @param $keyPath
	 * @param array $options
	 */
	public function __invoke($expression, array $arr, $keyPath, array $options = []) {
		$keyPathParts = explode('.', $keyPath);
		$value = RecursiveArray::get($arr, $keyPathParts);
		if(call_user_func($this->validator, $value)) {
			$this->whereBuilder->where($expression, $value);
		}
	}
}
