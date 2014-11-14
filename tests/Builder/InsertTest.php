<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\InsertTest\TestInsert;

class InsertTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestInsert::create()
		->into('travis#test1')
		->addExpr('last_update=NOW()')
		->asString();

		$this->assertEquals($query, 'INSERT'.' INTO travis_test.test1 SET last_update=NOW() ;');
	}
}
