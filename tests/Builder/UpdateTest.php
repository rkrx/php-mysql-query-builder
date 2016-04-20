<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\UpdateTest\TestUpdate;
use Phake;

class UpdateTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$sql = TestUpdate::create()
		->table('travis#test1')
		->set('field1', 1)
		->asString();
		$this->assertEquals("UPDATE\n\ttravis_test.test1\nSET\n\t`field1`='1'\n", $sql);
	}

	public function testMultipleTables() {
		$sql = TestUpdate::create()
		->table('t1', 'travis#test1')
		->table('t2', 'travis#test2')
		->set('t1', 'field1', 1)
		->asString();
		$this->assertEquals("UPDATE\n\ttravis_test.test1 t1,\n\ttravis_test.test2 t2\nSET\n\t`t1`='field1'\n", $sql);
	}

	public function testJoin() {
		$sql = TestUpdate::create()
		->table('t1', 'travis#test1')
		->joinInner('t2', 'travis#test2', 't1.field1 = t2.field1')
		->set('t1.field1', 1)
		->asString();
		$this->assertEquals("UPDATE\n\ttravis_test.test1 t1\nINNER JOIN\n\ttravis_test.test2 t2 ON t1.field1 = t2.field1\nSET\n\t`t1`.`field1`='1'\n", $sql);
	}

	public function testSet() {
		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1'\n", $sql);
	}

	public function testSetDefault() {
		$sql = TestUpdate::create()
			->table('test1')
			->setDefault('field1')
			->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`=DEFAULT\n", $sql);
	}

	public function testSetExpr() {
		$sql = TestUpdate::create()
		->table('test1')
		->setExpr('field1=1')
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\tfield1=1\n", $sql);
	}

	public function testSetExprWithParams() {
		$sql = TestUpdate::create()
		->table('test1')
		->setExpr('field1=COALESCE(?, ?)', 1, 2)
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\tfield1=COALESCE('1', '2')\n", $sql);
	}

	public function testSetAll1() {
		$sql = TestUpdate::create()
		->table('test1')
		->setAll(['field1' => 1, 'field2' => 2, 'field3' => 3], ['field1', 'field2'])
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1',\n\t`field2`='2'\n", $sql);
	}

	public function testSetAll2() {
		$db = Phake::mock('Kir\\MySQL\\Databases\\MySQL');
		$reg = Phake::mock('Kir\\MySQL\\Tools\\AliasRegistry');
		Phake::when($db)->__call('getTableFields', ['test1'])->thenReturn(['field1', 'field2']);
		Phake::when($db)->__call('quoteField', [Phake::anyParameters()])->thenGetReturnByLambda(function ($fieldName) { return "`{$fieldName}`"; });
		Phake::when($db)->__call('quote', [Phake::anyParameters()])->thenGetReturnByLambda(function ($value) { return "'{$value}'"; });
		Phake::when($db)->__call('getAliasRegistry', [])->thenReturn($reg);
		$sql = (new TestUpdate($db))
		->table('test1')
		->setAll(['field1' => 1, 'field2' => 2, 'field3' => 3])
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1',\n\t`field2`='2'\n", $sql);
	}

	public function testWhere() {
		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->where('field1=?', 2)
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1'\nWHERE\n\t(field1='2')\n", $sql);
	}

	public function testOrder() {
		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->orderBy('field1', 'DESC')
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1'\nORDER BY\n\tfield1 DESC\n", $sql);
	}

	public function testLimit() {
		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->limit(10)
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1'\nLIMIT\n\t10\n", $sql);

		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->limit(10)
		->offset(10)
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1'\nLIMIT\n\t10\nOFFSET\n\t10\n", $sql);
	}

	public function testMask() {
		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->set('field2', 2)
		->setMask(['field1'])
		->limit(10)
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1'\nLIMIT\n\t10\n", $sql);
	}

	public function testDBExpr() {
		$sql = TestUpdate::create()
		->table('test1')
		->set('field1', 1)
		->set('field2', new DBExpr('NOW()'))
		->limit(10)
		->asString();
		$this->assertEquals("UPDATE\n\ttest1\nSET\n\t`field1`='1',\n\t`field2`=NOW()\nLIMIT\n\t10\n", $sql);
	}
}
