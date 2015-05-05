<?php
namespace Kir\MySQL\Databases;

use Kir\FakePDO\EventHandlers\RegistryEventHandler;
use Kir\FakePDO\FakePDO;

class MySQLTest extends \PHPUnit_Framework_TestCase {
	private $errorLvl;

	protected function setUp() {
		$this->errorLvl = error_reporting();
		error_reporting(E_ALL ^ E_STRICT);
	}

	protected function tearDown() {
		error_reporting($this->errorLvl);
	}

	public function testTransactionTries1() {
		$pdo = new FakePDO();
		$mysql = new MySQL($pdo);

		$this->setExpectedException('Exception', '5');

		$mysql->transaction(5, function () {
			static $i;
			$i++;
			throw new \Exception($i);
		});
	}

	public function testTransactionTries2() {
		$pdo = new FakePDO();
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
		$eventHandler = new RegistryEventHandler();

		$eventHandler->add('PDOStatement::fetchAll', function ($event) {
			return [['Field' => 'a'], ['Field' => 'b'], ['Field' => 'c']];
		});

		$pdo = new FakePDO($eventHandler);

		$mysql = new MySQL($pdo);
		$mysql->getAliasRegistry()->add('test', 'test__');
		$fields = $mysql->getTableFields('test#table');

		$this->assertEquals(array('a', 'b', 'c'), $fields);
	}
}
