<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\InsertTest\TestInsert;
use Kir\MySQL\Builder\SelectTest\TestSelectMySQL;
use Kir\MySQL\Common\DBTestCase;

class InsertTest extends DBTestCase {
	public function testAlias(): void {
		$query = TestInsert::create()
		->into('travis#test1')
		->addExpr('last_update=NOW()')
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\tlast_update=NOW()\n", $query);
	}

	public function testAddExpr(): void {
		$query = TestInsert::create()
		->into('test1')
		->addExpr('last_update=NOW()')
		->asString();
		self::assertEquals("INSERT INTO\n\ttest1\nSET\n\tlast_update=NOW()\n", $query);
	}

	public function testMassInsert(): void {
		$select = TestSelectMySQL::create()
		->fields(['a' => 'b'])
		->from('oi', 'travis#test1')
		->where('1!=2');

		$query = TestInsert::create()
		->into('travis#test2')
		->from($select)
		->updateExpr('a = VALUES(a)')
		->asString();

		self::assertEquals("INSERT INTO\n\ttravis_test.test2\n\t(a)\nSELECT\n\tb AS `a`\nFROM\n\ttravis_test.test1 oi\nWHERE\n\t(1!=2)\nON DUPLICATE KEY UPDATE\n\ta = VALUES(a)\n", $query);
	}

	public function testAddAll(): void {
		$query = TestInsert::create()
		->into('travis#test1')
		->addAll(['field1' => 123, 'field2' => 456])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123,\n\t`field2`=456\n", $query);

		$query = TestInsert::create()
		->into('travis#test1')
		->addAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123\n", $query);

		$query = TestInsert::create()
		->into('travis#test1')
		->addAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123\n", $query);
	}

	public function testUpdateAll(): void {
		$query = $this->insert()
		->into('travis#test1')
		->add('field1', 123)
		->updateAll(['field1' => 123, 'field2' => 456])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123\nON DUPLICATE KEY UPDATE\n\t`field1`=123,\n\t`field2`=456\n", $query);

		$query = $this->insert()
		->into('travis#test1')
		->add('field1', 123)
		->updateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123\nON DUPLICATE KEY UPDATE\n\t`field1`=123\n", $query);

		$query = $this->insert()
		->into('travis#test1')
		->add('field1', 123)
		->updateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123\nON DUPLICATE KEY UPDATE\n\t`field1`=123\n", $query);
	}

	public function testAddOrUpdateAll(): void {
		$query = $this->insert()
		->into('travis#test1')
		->addOrUpdateAll(['field1' => 123, 'field2' => 456])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123,\n\t`field2`=456\nON DUPLICATE KEY UPDATE\n\t`field1`=123,\n\t`field2`=456\n", $query);

		$query = $this->insert()
		->into('travis#test1')
		->addOrUpdateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123\nON DUPLICATE KEY UPDATE\n\t`field1`=123\n", $query);

		$query = $this->insert()
		->into('travis#test1')
		->addOrUpdateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		self::assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`=123\nON DUPLICATE KEY UPDATE\n\t`field1`=123\n", $query);
	}

	public function testMask(): void {
		$sql = TestInsert::create()
		->into('test')
		->addOrUpdate('field1', 1)
		->addOrUpdate('field2', 2)
		->setMask(['field1'])
		->asString();
		self::assertEquals("INSERT INTO\n\ttest\nSET\n\t`field1`=1\nON DUPLICATE KEY UPDATE\n\t`field1`=1\n", $sql);
	}

	public function testExprWithParams(): void {
		$sql = TestInsert::create()
		->into('test')
		->addExpr('a=?', 'a')
		->updateExpr('b=?', 'b')
		->addOrUpdateExpr('c=?', 'c')
		->asString();
		self::assertEquals("INSERT INTO\n\ttest\nSET\n\ta='a',\n\tc='c'\nON DUPLICATE KEY UPDATE\n\tb='b',\n\tc='c'\n", $sql);
	}

	public function testDBExpr(): void {
		$sql = TestInsert::create()
		->into('test')
		->addExpr('a=?', new DBExpr('NOW()'))
		->updateExpr('b=?', new DBExpr('NOW()'))
		->addOrUpdateExpr('c=?', new DBExpr('NOW()'))
		->asString();
		self::assertEquals("INSERT INTO\n\ttest\nSET\n\ta=NOW(),\n\tc=NOW()\nON DUPLICATE KEY UPDATE\n\tb=NOW(),\n\tc=NOW()\n", $sql);
	}
}
