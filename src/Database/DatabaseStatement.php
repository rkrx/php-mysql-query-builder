<?php
namespace Kir\MySQL\Database;

use PDO;
use PDOStatement;

interface DatabaseStatement {
	/**
	 * @return PDOStatement
	 */
	public function getStatement();

	/**
	 * @param array $params
	 * @return bool
	 */
	public function execute(array $params = []);

	/**
	 * @return array
	 */
	public function fetchAll();

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
	public function closeCursor();

	/**
	 * @return int
	 */
	public function columnCount();

	/**
	 * @param int $columnNo
	 * @return array
	 */
	public function getColumnMeta($columnNo);
}
