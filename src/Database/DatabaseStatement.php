<?php
namespace Kir\MySQL\Database;

use Kir\MySQL\Exceptions\SqlException;
use PDO;
use PDOStatement;

interface DatabaseStatement {
	/**
	 * @return PDOStatement<mixed>
	 */
	public function getStatement(): PDOStatement;

	/**
	 * @param array<string, mixed> $params
	 * @throws SqlException
	 * @return $this
	 */
	public function execute(array $params = []);

	/**
	 * @param int $fetchStyle
	 * @param mixed $fetchArgument
	 * @param array<mixed, mixed> $ctorArgs
	 * @return array<mixed, mixed>
	 */
	public function fetchAll($fetchStyle = PDO::FETCH_ASSOC, $fetchArgument = null, array $ctorArgs = []): array;

	/**
	 * @param int $fetchStyle
	 * @param int $cursorOrientation
	 * @param int $cursorOffset
	 * @return mixed
	 */
	public function fetch($fetchStyle = PDO::FETCH_ASSOC, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0);

	/**
	 * @param int $columnNo
	 * @return mixed
	 */
	public function fetchColumn($columnNo = 0);

	/**
	 * @return bool
	 */
	public function closeCursor(): bool;

	/**
	 * @return int
	 */
	public function columnCount(): int;

	/**
	 * @param int $columnNo
	 * @return null|array<string, mixed>
	 */
	public function getColumnMeta(int $columnNo): ?array;
}
