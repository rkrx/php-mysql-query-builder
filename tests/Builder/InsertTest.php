<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\InsertTest\TestInsert;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Phake;

class InsertTest extends \PHPUnit_Framework_TestCase {
	public function testAlias() {
		$query = TestInsert::create()
		->into('travis#test1')
		->addExpr('last_update=NOW()')
		->asString();
		$this->assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\tlast_update=NOW()\n", $query);
	}

	public function testAddExpr() {
		$query = TestInsert::create()
		->into('test1')
		->addExpr('last_update=NOW()')
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\tlast_update=NOW()\n", $query);
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

		$this->assertEquals("INSERT INTO\n\ttravis_test.test2\n\t(a)\nSELECT\n\tb AS `a`\nFROM\n\ttravis_test.test1 oi\nWHERE\n\t(1!=2)\nON DUPLICATE KEY UPDATE\n\ta = VALUES(a)\n", $query);
	}

	public function testAddAll() {
		$reg = Phake::mock('Kir\\MySQL\\Tools\\AliasRegistry');
		Phake::when($reg)->__call('get', ['travis'])->thenReturn('travis_test.');

		$db = Phake::mock('Kir\\MySQL\\Databases\\MySQL');
		Phake::when($db)->__call('getTableFields', ['test1'])->thenReturn(['field1', 'field2']);
		Phake::when($db)->__call('getTableFields', ['travis_test.test1'])->thenReturn(['field1', 'field2']);
		Phake::when($db)->__call('quoteField', [Phake::anyParameters()])->thenGetReturnByLambda(function ($fieldName) { return "`{$fieldName}`"; });
		Phake::when($db)->__call('quote', [Phake::anyParameters()])->thenGetReturnByLambda(function ($value) { return "'{$value}'"; });
		Phake::when($db)->__call('getAliasRegistry', [])->thenReturn($reg);

		$query = (new TestInsert($db))
		->into('test1')
		->addAll(['field1' => 123, 'field2' => 456])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\t`field1`='123',\n\t`field2`='456'\n", $query);

		$query = (new TestInsert($db))
		->into('test1')
		->addAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\t`field1`='123'\n", $query);

		$query = (new TestInsert($db))
		->into('travis#test1')
		->addAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`='123'\n", $query);
	}

	public function testUpdateAll() {
		$reg = Phake::mock('Kir\\MySQL\\Tools\\AliasRegistry');
		Phake::when($reg)->__call('get', ['travis'])->thenReturn('travis_test.');

		$db = Phake::mock('Kir\\MySQL\\Databases\\MySQL');
		Phake::when($db)->__call('getTableFields', ['test1'])->thenReturn(['field1', 'field2']);
		Phake::when($db)->__call('getTableFields', ['travis_test.test1'])->thenReturn(['field1', 'field2']);
		Phake::when($db)->__call('quoteField', [Phake::anyParameters()])->thenGetReturnByLambda(function ($fieldName) { return "`{$fieldName}`"; });
		Phake::when($db)->__call('quote', [Phake::anyParameters()])->thenGetReturnByLambda(function ($value) { return "'{$value}'"; });
		Phake::when($db)->__call('getAliasRegistry', [])->thenReturn($reg);

		$query = (new TestInsert($db))
		->into('test1')
		->add('field1', 123)
		->updateAll(['field1' => 123, 'field2' => 456])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\t`field1`='123'\nON DUPLICATE KEY UPDATE\n\t`field1`='123',\n\t`field2`='456'\n", $query);

		$query = (new TestInsert($db))
		->into('test1')
		->add('field1', 123)
		->updateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\t`field1`='123'\nON DUPLICATE KEY UPDATE\n\t`field1`='123'\n", $query);

		$query = (new TestInsert($db))
		->into('travis#test1')
		->add('field1', 123)
		->updateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`='123'\nON DUPLICATE KEY UPDATE\n\t`field1`='123'\n", $query);
	}

	public function testAddOrUpdateAll() {
		$reg = Phake::mock('Kir\\MySQL\\Tools\\AliasRegistry');
		Phake::when($reg)->__call('get', ['travis'])->thenReturn('travis_test.');

		$db = Phake::mock('Kir\\MySQL\\Databases\\MySQL');
		Phake::when($db)->__call('getTableFields', ['test1'])->thenReturn(['field1', 'field2']);
		Phake::when($db)->__call('getTableFields', ['travis_test.test1'])->thenReturn(['field1', 'field2']);
		Phake::when($db)->__call('quoteField', [Phake::anyParameters()])->thenGetReturnByLambda(function ($fieldName) { return "`{$fieldName}`"; });
		Phake::when($db)->__call('quote', [Phake::anyParameters()])->thenGetReturnByLambda(function ($value) { return "'{$value}'"; });
		Phake::when($db)->__call('getAliasRegistry', [])->thenReturn($reg);

		$query = (new TestInsert($db))
		->into('test1')
		->addOrUpdateAll(['field1' => 123, 'field2' => 456])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\t`field1`='123',\n\t`field2`='456'\nON DUPLICATE KEY UPDATE\n\t`field1`='123',\n\t`field2`='456'\n", $query);

		$query = (new TestInsert($db))
		->into('test1')
		->addOrUpdateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest1\nSET\n\t`field1`='123'\nON DUPLICATE KEY UPDATE\n\t`field1`='123'\n", $query);

		$query = (new TestInsert($db))
		->into('travis#test1')
		->addOrUpdateAll(['field1' => 123, 'field2' => 456], ['field1'])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttravis_test.test1\nSET\n\t`field1`='123'\nON DUPLICATE KEY UPDATE\n\t`field1`='123'\n", $query);
	}

	public function testMask() {
		$sql = TestInsert::create()
		->into('test')
		->addOrUpdate('field1', 1)
		->addOrUpdate('field2', 2)
		->setMask(['field1'])
		->asString();
		$this->assertEquals("INSERT INTO\n\ttest\nSET\n\t`field1`='1'\nON DUPLICATE KEY UPDATE\n\t`field1`='1'\n", $sql);
	}
}
