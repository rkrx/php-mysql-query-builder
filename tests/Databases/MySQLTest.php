<?php
namespace Kir\MySQL\Databases;

use PDO;

class MySQLTest extends \PHPUnit_Framework_TestCase {
	public function testTransactionTries1() {
		$pdo = new PDO('sqlite::memory:');
		$mysql = new MySQL($pdo);

		$this->setExpectedException('Exception', '5');

		$mysql->transaction(5, function () {
			static $i;
			$i++;
			throw new \Exception($i);
		});
	}

	public function testTransactionTries2() {
		$pdo = new PDO('sqlite::memory:');
		$mysql = new MySQL($pdo);

		$result = $mysql->transaction(5, function () {
			static $i;
			$i++;
			if($i < 5) {
				throw new \Exception($i);
			}
			return $i;
		});

		$this->assertEquals(5, $result);
	}
}
