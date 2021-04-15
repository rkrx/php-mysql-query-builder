<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Databases\TestDB;
use Kir\MySQL\QueryLogger\ClosureQueryLogger;
use PHPUnit\Framework\TestCase;

class InterceptionTest extends TestCase {
	public function testQuery(): void {
		$db = new TestDB();
		$db->exec('USE mysql');
		$queries = [];
		$db->getQueryLoggers()->add(new ClosureQueryLogger(function ($query, $duration) use (&$queries) {
			$queries[$query] = 1000;
		}));
		$stmt = $db->query('SHOW TABLES');
		$stmt->execute();
		$this->assertArrayHasKey('SHOW TABLES', $queries);
		$this->assertEquals(1000, $queries['SHOW TABLES']);
	}
}
