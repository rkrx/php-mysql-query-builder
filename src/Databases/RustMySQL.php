<?php

namespace Kir\MySQL\Databases;

use Kir\MySQL\Builder;
use Kir\MySQL\Rust\RustRunnableDelete;
use Kir\MySQL\Rust\RustRunnableInsert;
use Kir\MySQL\Rust\RustRunnableSelect;
use Kir\MySQL\Rust\RustRunnableUpdate;
use PDO;

class RustMySQL extends MySQL {
	/** @var array<string, mixed> */
	private array $rustOptions;

	/**
	 * @param PDO $pdo
	 * @param array<string, mixed> $options
	 */
	public function __construct(PDO $pdo, array $options = []) {
		parent::__construct($pdo, $options);
		$this->rustOptions = array_merge([
			'select-options' => [],
			'insert-options' => [],
			'update-options' => [],
			'delete-options' => [],
		], $options);
	}

	/**
	 * @param array<string|int, string>|null $fields
	 */
	public function select(?array $fields = null): Builder\RunnableSelect {
		$select = new RustRunnableSelect($this, $this->rustOptions['select-options']);
		if($fields !== null) {
			$select->fields($fields);
		}

		return $select;
	}

	/**
	 * @param null|array<string|int, string> $fields
	 */
	public function insert(?array $fields = null): Builder\RunnableInsert {
		$insert = new RustRunnableInsert($this, $this->rustOptions['insert-options']);
		if($fields !== null) {
			$insert->addAll($fields);
		}

		return $insert;
	}

	/**
	 * @param array<string|int, string>|null $fields
	 */
	public function update(?array $fields = null): Builder\RunnableUpdate {
		$update = new RustRunnableUpdate($this, $this->rustOptions['update-options']);
		if($fields !== null) {
			$update->setAll($fields);
		}

		return $update;
	}

	public function delete(): Builder\RunnableDelete {
		return new RustRunnableDelete($this, $this->rustOptions['delete-options']);
	}
}
