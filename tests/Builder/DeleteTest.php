<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Common\DBTestCase;

class DeleteTest extends DBTestCase {
	public function testAlias(): void {
		$query = $this->delete()
		->from('t', 'travis#test1')
		->where('t.a = 1')
		->asString();
		self::assertEquals("DELETE t FROM\n\ttravis_test.test1 t\nWHERE\n\t(t.a = 1)\n", $query);
	}

	public function testMultipleTables(): void {
		$sql = $this->delete()
		->from('t1', 'travis#test1')
		->from('t2', 'travis#test2')
		->where('t1.a = 1')
		->asString();
		self::assertEquals("DELETE t1, t2 FROM\n\ttravis_test.test1 t1,\n\ttravis_test.test2 t2\nWHERE\n\t(t1.a = 1)\n", $sql);
	}

	public function testJoins(): void {
		$sql = $this->delete()
		->from('t1', 'travis#test1')
		->joinInner('t2', 'travis#test2', 't1.id = t2.id')
		->asString();
		self::assertEquals("DELETE t1 FROM\n\ttravis_test.test1 t1\nINNER JOIN\n\ttravis_test.test2 t2 ON t1.id = t2.id\n", $sql);
	}

	public function testWhere(): void {
		$sql = $this->delete()
		->from('travis#test1')
		->where('field1=?', 1)
		->where('field2 != field1')
		->asString();
		self::assertEquals("DELETE FROM\n\ttravis_test.test1\nWHERE\n\t(field1=1)\n\tAND\n\t(field2 != field1)\n", $sql);
	}

	public function testWhereViaArray(): void {
		$sql = $this->delete()
		->from('travis#test1')
		->where(['field1' => 1, 'field2' => 'aaa'])
		->asString();
		self::assertEquals("DELETE FROM\n\ttravis_test.test1\nWHERE\n\t(`field1`=1)\n\tAND\n\t(`field2`='aaa')\n", $sql);
	}

	public function testDBExpr(): void {
		$sql = $this->delete()
		->from('travis#test1')
		->where('field1=?', new DBExpr('NOW()'))
		->asString();
		self::assertEquals("DELETE FROM\n\ttravis_test.test1\nWHERE\n\t(field1=NOW())\n", $sql);
	}

	public function testOrderBy(): void {
		$sql = $this->delete()
		->from('travis#test1')
		->orderBy('field1', 'DESC')
		->asString();
		self::assertEquals("DELETE FROM\n\ttravis_test.test1\nORDER BY\n\tfield1 DESC\n", $sql);
	}

	public function testLimit(): void {
		$sql = $this->delete()
		->from('travis#test1')
		->limit(10)
		->asString();
		self::assertEquals("DELETE FROM\n\ttravis_test.test1\nLIMIT\n\t10\n", $sql);
	}

	public function testOffset(): void {
		$sql = $this->delete()
		->from('travis#test1')
		->offset(10)
		->asString();
		self::assertEquals("DELETE FROM\n\ttravis_test.test1\nOFFSET\n\t10\n", $sql);
	}
}
