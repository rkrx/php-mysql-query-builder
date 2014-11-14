<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\DeleteTest\TestDelete;

class DeleteTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestDelete::create()
		->from('travis#test1')
		->where('a = 1')
		->asString();
		$this->assertEquals('DELETE FROM travis_test.test1 WHERE (a = 1) ;', $query);
	}
}
