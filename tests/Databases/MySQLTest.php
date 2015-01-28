<?php
namespace Kir\MySQL\Databases;

use Pseudo\Pdo;

class MySQLTest extends \PHPUnit_Framework_TestCase {
	public function testTransactionTries1() {
		$pdo = new Pdo();
		$mysql = new MySQL($pdo);

		$this->setExpectedException('Exception', '5');

		$mysql->transaction(5, function () {
			static $i;
			$i++;
			throw new \Exception($i);
		});
	}

	public function testTransactionTries2() {
		$pdo = new Pdo();
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

	public function testGetTableFields() {
		$pdo = new Pdo();
		$pdo->mock('DESCRIBE test__table', [['Field' => 'a'], ['Field' => 'b'], ['Field' => 'c']]);

		$mysql = new MySQL($pdo);
		$mysql->getAliasRegistry()->add('test', 'test__');
		$fields = $mysql->getTableFields('test#table');

		$this->assertEquals(array('a', 'b', 'c'), $fields);
	}
}
