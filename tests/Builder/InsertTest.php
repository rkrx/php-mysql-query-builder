<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\InsertTest\TestInsert;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Database;
use Kir\MySQL\Tools\AliasRegistry;
use Phake;

class InsertTest extends \PHPUnit_Framework_TestCase {
	public function XtestAlias() {
		$reg = new AliasRegistry();
		$reg->add('travis', 'travis_test.');

		$db = Phake::mock(Database::class);
		Phake::when($db)->__call('getTableFields', ['travis_test.test1'])->thenReturn([]);
		Phake::when($db)->__call('getAliasRegistry', [])->thenReturn($reg);

		$query = (new TestInsert($db))
		->into('travis#test1')
		->addExpr('last_update=NOW()')
		->asString();

		$this->assertEquals($query, 'INSERT'.' INTO travis_test.test1 SET last_update=NOW() ;');
	}

	public function testMassInsert() {
		$select = TestSelect::create()
		->fields(['a' => 'b'])
		->from('oi', 'travis#test1')
		->where('1!=2');

		$query = TestInsert::create()
		->into('travis#test2')
		->from($select)
		->updateExpr('a = VALUES(a)')
		->asString();

		$this->assertEquals('INSERT INTO travis_test.test2 (a) SELECT b AS `a` FROM travis_test.test1 oi WHERE (1!=2) ON DUPLICATE KEY UPDATE a = VALUES(a) ;', $query);
	}
}
