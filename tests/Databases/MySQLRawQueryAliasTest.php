<?php

namespace Kir\MySQL\Databases;

use Kir\MySQL\Databases\Mock\MockPDOStatement;
use Kir\MySQL\QueryLogger\ClosureQueryLogger;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class MySQLRawQueryAliasTest extends TestCase {
	public function testRawQueryMethodsReplaceAliasesBeforePdoAndLogging(): void {
		$pdo = new class extends PDO {
			/** @var list<string> */
			public array $queries = [];
			/** @var list<string> */
			public array $preparedQueries = [];

			public function __construct() {}

			public function getAttribute(int $attribute): mixed {
				return $attribute === PDO::ATTR_ERRMODE ? PDO::ERRMODE_EXCEPTION : null;
			}

			public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
				$this->queries[] = $query;

				return new MockPDOStatement(static fn() => [], 'dql');
			}

			/** @param array<mixed, mixed> $options */
			public function prepare(string $query, array $options = []): PDOStatement|false {
				$this->preparedQueries[] = $query;

				return new MockPDOStatement(static fn() => 0, 'ddl');
			}
		};
		$db = new MySQL($pdo);
		$db->getAliasRegistry()->add('shop', '`dv-shop`.shop__');
		$loggedQueries = [];
		$db->getQueryLoggers()->add(new ClosureQueryLogger(
			static function(string $query) use (&$loggedQueries): void {
				$loggedQueries[] = $query;
			},
		));

		$queryStatement = $db->query('SELECT * FROM shop#products');
		$preparedStatement = $db->prepare('SELECT * FROM shop#orders WHERE id = ?');
		$result = $db->exec('DELETE FROM shop#products WHERE id = :id', ['id' => 1]);

		$this->assertSame(['SELECT * FROM `dv-shop`.shop__products'], $pdo->queries);
		$this->assertSame([
			'SELECT * FROM `dv-shop`.shop__orders WHERE id = ?',
			'DELETE FROM `dv-shop`.shop__products WHERE id = :id',
		], $pdo->preparedQueries);
		$this->assertSame('SELECT * FROM `dv-shop`.shop__products', (string) $queryStatement);
		$this->assertSame('SELECT * FROM `dv-shop`.shop__orders WHERE id = ?', (string) $preparedStatement);
		$this->assertSame(0, $result);
		$this->assertContains('SELECT * FROM `dv-shop`.shop__products', $loggedQueries);
		$this->assertContains('DELETE FROM `dv-shop`.shop__products WHERE id = :id', $loggedQueries);
	}
}
