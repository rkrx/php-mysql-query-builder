<?php
namespace Kir\MySQL\Builder\Expr;

use Kir\MySQL\Builder\Helpers\RecursiveStructureAccess;
use RuntimeException;

class DBExprFilter implements OptionalExpression {
	/** @var mixed */
	private $expression;
	/** @var bool */
	private $hasValue;
	/** @var mixed */
	private $value;
	/** @var string[] */
	private $keyPath;
	/** @var null|callable(mixed): bool */
	private $validator;
	/** @var callable(bool, array{key: mixed, value: string}) */
	private $validationResultHandler;

	/**
	 * @param string $expression
	 * @param array<string, mixed> $data
	 * @param string|string[] $keyPath
	 * @param callable|null $validator
	 * @param callable|null $validationResultHandler
	 */
	public function __construct(string $expression, array $data, $keyPath, $validator = null, $validationResultHandler = null) {
		$this->expression = $expression;
		$this->keyPath = $this->buildKey($keyPath);
		$this->hasValue = RecursiveStructureAccess::recursiveHas($data, $this->keyPath);
		$this->value = RecursiveStructureAccess::recursiveGet($data, $this->keyPath, null);
		if($validator === null) {
			$validator = function() {
				return true;
			};
		}
		$this->validator = $validator;
		if($validationResultHandler === null) {
			$validationResultHandler = static function () {};
		}
		$this->validationResultHandler = $validationResultHandler;
	}

	/**
	 * @return string
	 */
	public function getExpression(): string {
		return $this->expression;
	}

	/**
	 * @return bool
	 */
	public function isValid(): bool {
		if(!$this->hasValue) {
			return false;
		}
		if($this->validator !== null) {
			$result = call_user_func($this->validator, $this->value);
			call_user_func($this->validationResultHandler, $result, [
				'value' => $this->value,
				'key' => implode('.', $this->keyPath),
			]);
			return $result;
		}
		return true;
	}

	/**
	 * @return array{mixed}
	 */
	public function getValue(): array {
		return [$this->value];
	}

	/**
	 * @param string|string[] $keyPath
	 * @return string[]
	 */
	private function buildKey($keyPath): array {
		if(is_string($keyPath)) {
			$keyPath = explode('.', $keyPath);
		}
		if(!is_array($keyPath)) {
			throw new RuntimeException('Invalid key');
		}
		return $keyPath;
	}

	/**
	 * @param array<string, mixed> $array
	 * @return bool
	 */
	private function isValidArray(array $array): bool {
		$data = array_filter($array, function ($value) {
			if(is_array($value)) {
				return $this->isValidArray($value);
			}
			return (string) $value !== '';
		});
		return count($data) > 0;
	}
}
