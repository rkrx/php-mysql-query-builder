<?php
namespace Kir\MySQL\Builder\Expr;

use RuntimeException;

class DBExprFilter implements OptionalExpression {
	/** @var mixed */
	private $expression;
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
		$this->value = $this->recursiveGet($data, $this->keyPath, null);
		if($validator === null) {
			$validator = function ($data) {
				if(is_array($data)) {
					return $this->isValidArray($data);
				}
				return (string) $data !== '';
			};
		}
		if($validationResultHandler === null) {
			$validationResultHandler = static function () {};
		}
		$this->validator = $validator;
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
		$result = true;
		if($this->validator !== null) {
			$result = call_user_func($this->validator, $this->value);
			call_user_func($this->validationResultHandler, $result, [
				'value' => $this->value,
				'key' => implode('.', $this->keyPath),
			]);
		}
		return $result;
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

	/**
	 * @param array<string, mixed> $array
	 * @param array<int, string> $path
	 * @param mixed $default
	 * @return mixed
	 */
	private function recursiveGet(array $array, array $path, $default) {
		$count = count($path);
		if (!$count) {
			return $default;
		}
		foreach($path as $idxValue) {
			$part = $idxValue;
			if(!array_key_exists($part, $array)) {
				return $default;
			}
			$array = $array[$part];
		}
		return $array;
	}
}
