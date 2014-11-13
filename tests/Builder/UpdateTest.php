<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\UpdateTest\TestUpdate;

class UpdateTest extends \PHPUnit_Framework_TestCase {
	public function testWhere() {
		$sql = TestUpdate::create()
		->table('test')
		->set('ean', '0000000001011')
		->asString();
		$this->assertEquals('UPDATE test SET `ean` = \'0000000001011\' ;', $sql);
	}

	public function testAlias() {
		$sql = TestUpdate::create()
		->table('orders#test')
		->set('ean', '0000000001011')
		->asString();
		$this->assertEquals('UPDATE shop.orders_test SET `ean` = \'0000000001011\' ;', $sql);
	}
}
 