<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\DeleteTest\TestDelete;

class DeleteTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestDelete::create()
		->from('t', 'travis#test1')
		->where('t.a = 1')
		->asString();
		$this->assertEquals("DELETE t FROM\n\ttravis_test.test1 t\nWHERE\n\t(t.a = 1)\n", $query);
	}

	public function testMultipleTables() {
		$sql = TestDelete::create()
		->from('t1', 'travis#test1')
		->from('t2', 'travis#test2')
		->where('t1.a = 1')
		->asString();
		$this->assertEquals("DELETE t1, t2 FROM\n\ttravis_test.test1 t1,\n\ttravis_test.test2 t2\nWHERE\n\t(t1.a = 1)\n", $sql);
	}

	public function testJoins() {
		$sql = TestDelete::create()
		->from('t1', 'travis#test1')
		->joinInner('t2', 'travis#test2', 't1.id = t2.id')
		->asString();
		$this->assertEquals("DELETE t1 FROM\n\ttravis_test.test1 t1\nINNER JOIN\n\ttravis_test.test2 t2 ON t1.id = t2.id\n", $sql);
	}

	public function testWhere() {
		$sql = TestDelete::create()
		->from('travis#test1')
		->where('field1=?', 1)
		->where('field2 != field1')
		->asString();
		$this->assertEquals("DELETE FROM\n\ttravis_test.test1\nWHERE\n\t(field1='1')\n\tAND\n\t(field2 != field1)\n", $sql);
	}

	public function testWhereViaArray() {
		$sql = TestDelete::create()
		->from('travis#test1')
		->where(['field1' => 1, 'field2' => 'aaa'])
		->asString();
		$this->assertEquals("DELETE FROM\n\ttravis_test.test1\nWHERE\n\t(`field1`='1')\n\tAND\n\t(`field2`='aaa')\n", $sql);
	}

	public function testDBExpr() {
		$sql = TestDelete::create()
		->from('travis#test1')
		->where('field1=?', new DBExpr('NOW()'))
		->asString();
		$this->assertEquals("DELETE FROM\n\ttravis_test.test1\nWHERE\n\t(field1=NOW())\n", $sql);
	}

	public function testOrderBy() {
		$sql = TestDelete::create()
		->from('travis#test1')
		->orderBy('field1', 'DESC')
		->asString();
		$this->assertEquals("DELETE FROM\n\ttravis_test.test1\nORDER BY\n\tfield1 DESC\n", $sql);
	}

	public function testLimit() {
		$sql = TestDelete::create()
		->from('travis#test1')
		->limit(10)
		->asString();
		$this->assertEquals("DELETE FROM\n\ttravis_test.test1\nLIMIT\n\t10\n", $sql);
	}

	public function testOffset() {
		$sql = TestDelete::create()
		->from('travis#test1')
		->offset(10)
		->asString();
		$this->assertEquals("DELETE FROM\n\ttravis_test.test1\nOFFSET\n\t10\n", $sql);
	}
}
