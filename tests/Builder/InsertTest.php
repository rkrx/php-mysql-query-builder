<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\InsertTest\TestInsert;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Database;
use Kir\MySQL\Tools\AliasRegistry;
use Phake;

class InsertTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestInsert::create()
		->into('travis#test1')
		->addExpr('last_update=NOW()')
		->asString();
		$this->assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\tlast_update=NOW()\n;\n", $query);
	}

	public function testAddExpr() {
		$query = TestInsert::create()
		->into('test1')
		->addExpr('last_update=NOW()')
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\tlast_update=NOW()\n;\n", $query);
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

		$this->assertEquals("INSERT INTO\n\ttravis_test.test2\n\t(a)\nSELECT\n\tb AS `a`\nFROM\n\ttravis_test.test1 oi\nWHERE\n\t(1!=2)\nON DUPLICATE KEY UPDATE\n\ta = VALUES(a)\n;\n", $query);
	}
}
