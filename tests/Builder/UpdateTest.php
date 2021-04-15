<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Common\DBTestCase;

class UpdateTest extends DBTestCase {
	public function testAlias(): void {
		$sql = $this->update()
		->table('travis#test1')
		->set('field1', 1)
		->asString();
		self::assertEquals("UPDATE\n\ttravis_test.test1\nSET\n\t`field1`=1\n", $sql);
	}

	public function testMultipleTables(): void {
		$sql = $this->update()
		->table('t1', 'travis#test1')
		->table('t2', 'travis#test2')
		->set('t1.field1', 1)
		->asString();
		self::assertEquals("UPDATE\n\ttravis_test.test1 t1,\n\ttravis_test.test2 t2\nSET\n\t`t1`.`field1`=1\n", $sql);
	}

	public function testJoin(): void {
		$sql = $this->update()
		->table('t1', 'travis#test1')
		->joinInner('t2', 'travis#test2', 't1.field1 = t2.field1')
		->set('t1.field1', 1)
		->asString();
		self::assertEquals("UPDATE\n\ttravis_test.test1 t1\nINNER JOIN\n\ttravis_test.test2 t2 ON t1.field1 = t2.field1\nSET\n\t`t1`.`field1`=1\n", $sql);
	}

	public function testSet(): void {
		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1\n", $sql);
	}

	public function testSetDefault(): void {
		$sql = $this->update()
			->table('test1')
			->setDefault('field1')
			->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=DEFAULT\n", $sql);
	}

	public function testSetExpr(): void {
		$sql = $this->update()
		->table('test1')
		->setExpr('field1=1')
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\tfield1=1\n", $sql);
	}

	public function testSetExprWithParams(): void {
		$sql = $this->update()
		->table('test1')
		->setExpr('field1=COALESCE(?, ?)', 1, 2)
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\tfield1=COALESCE(1, 2)\n", $sql);
	}

	public function testSetAll1(): void {
		$sql = $this->update()
		->table('test1')
		->setAll(['field1' => 1, 'field2' => 2, 'field3' => 3], ['field1', 'field2'])
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1,\n\t`field2`=2\n", $sql);
	}

	public function testSetAll2(): void {
		$sql = $this->update()
		->table('test1')
		->setAll(['field1' => 1, 'field2' => 2, 'field3' => 3])
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1,\n\t`field2`=2,\n\t`field3`=3\n", $sql);
	}

	public function testWhere(): void {
		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->where('field1=?', 2)
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1\nWHERE\n\t(field1=2)\n", $sql);

		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->where(['field1' => 1, 'field2' => 'aaa'])
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1\nWHERE\n\t(`field1`=1)\n\tAND\n\t(`field2`='aaa')\n", $sql);
	}

	public function testOrder(): void {
		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->orderBy('field1', 'DESC')
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1\nORDER BY\n\tfield1 DESC\n", $sql);
	}

	public function testLimit(): void {
		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->limit(10)
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1\nLIMIT\n\t10\n", $sql);

		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->limit(10)
		->offset(10)
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1\nLIMIT\n\t10\nOFFSET\n\t10\n", $sql);
	}

	public function testMask(): void {
		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->set('field2', 2)
		->setMask(['field1'])
		->limit(10)
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1\nLIMIT\n\t10\n", $sql);
	}

	public function testDBExpr(): void {
		$sql = $this->update()
		->table('test1')
		->set('field1', 1)
		->set('field2', new DBExpr('NOW()'))
		->limit(10)
		->asString();
		self::assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=1,\n\t`field2`=NOW()\nLIMIT\n\t10\n", $sql);
	}
}
