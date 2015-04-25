<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Databases\TestDB;
use Kir\MySQL\QueryLogger\ClosureQueryLogger;

class InterceptionTest extends \PHPUnit_Framework_TestCase {
	public function testQuery() {
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