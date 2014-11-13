<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\DeleteTest\TestDelete;

class DeleteTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestDelete::create()
		->from('orders#test')
		->where('a = 1')
		->asString();
		$this->assertEquals('DELETE FROM shop.orders_test WHERE (a = 1) ;', $query);
	}
}
