<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\InsertTest\TestInsert;
use Kir\MySQL\Builder\SelectTest\TestSelect;

class InsertTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestInsert::create()
		->into('travis#test1')
		->addExpr('last_update=NOW()')
		->asString();

		$this->assertEquals($query, 'INSERT'.' INTO travis_test.test1 SET last_update=NOW() ;');
	}

	public function testMassInsert() {
		$select = TestSelect::create()
		->fields(['a' => 'b'])
		->from('oi', 'orders#items')
		->where('1!=2');

		$query = TestInsert::create()
		->into('orders#items')
		->from($select)
		->updateExpr('a = VALUES(a)')
		->debug()
		->asString();

		$this->assertEquals('', $query);
	}
}
