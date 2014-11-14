<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\UpdateTest\TestUpdate;

class UpdateTest extends \PHPUnit_Framework_TestCase {
	public function testWhere() {
		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->asString();
		$this->assertEquals('UPDATE test1 SET `field1` = \'1\' ;', $sql);
	}

	public function testAlias() {
		$sql = TestUpdate::create()
		->table('travis#test1')
		->set('field1', 1)
		->asString();
		$this->assertEquals('UPDATE travis_test.test1 SET `field1` = \'1\' ;', $sql);
	}
}
 