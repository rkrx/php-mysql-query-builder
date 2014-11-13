<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\InsertTest\TestInsert;

class InsertTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestInsert::create()
		->into('orders#items')
		->addExpr('last_update=NOW()')
		->asString();

		$this->assertEquals($query, 'INSERT'.' INTO shop.orders_items SET last_update=NOW() ;');
	}
}
