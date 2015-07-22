<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Databases\TestDB;

class SelectTestX extends \PHPUnit_Framework_TestCase {
	public function testAddition() {
		$str = TestSelect::create()->field('1+2')->asString();
		$this->assertEquals("SELECT\n\t1+2\n", $str);
	}

	public function testFrom() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
	}

	public function testMultipleFrom() {
		$str = TestSelect::create()
		->field('a')
		->from('t1', 'test1')
		->from('t2', 'test2')
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1,\n\ttest2 t2\n", $str);
	}

	public function testJoins() {
		$testFn = function ($method, $type) {
			$sql = TestSelect::create()
			->field('a')
			->from('t1', 'test1')
			->{$method}('t2', 'test2', 't2.id=t1.id')
			->asString();
			$this->assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\n{$type} JOIN\n\ttest2 t2 ON t2.id=t1.id\n", $sql);

			$sql = TestSelect::create()
			->field('a')
			->from('t1', 'test1')
			->{$method}('t2', 'test2', 't2.id=t1.id AND t2.id < ?', 1000)
			->asString();
			$this->assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\n{$type} JOIN\n\ttest2 t2 ON t2.id=t1.id AND t2.id < '1000'\n", $sql);
		};

		$testFn('joinInner', 'INNER');
		$testFn('joinLeft', 'LEFT');
		$testFn('joinRight', 'RIGHT');
	}

	public function testWhere() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a+1<2')
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a+1<2)\n", $str);

		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a < ?', 1000)
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a < '1000')\n", $str);

		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a < :0', 1000)
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a < '1000')\n", $str);
	}

	public function testHaving() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->having('a+1<2')
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nHAVING\n\t(a+1<2)\n", $str);
	}

	public function testOrder() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->orderBy('a', 'desc')
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nORDER BY\n\ta DESC\n", $str);
	}

	public function testGroup() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->groupBy('a', 'b', 'c')
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nGROUP BY\n\ta,\n\tb,\n\tc\n", $str);
	}

	public function testLimit() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t100\n", $str);
	}

	public function testOffset() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->offset(50)
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t100\nOFFSET\n\t50\n", $str);
	}

	public function testForUpdate() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->forUpdate()
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nFOR UPDATE\n", $str);
	}

	public function testInnerSelect() {
		$select = TestSelect::create()
		->from('a', 'table')
		->where('a.id=1');

		$str = (string) TestSelect::create()
		->from('t', $select)
		->asString();

		$this->assertEquals("SELECT\n\t*\nFROM\n\t(SELECT\n\t\t*\n\tFROM\n\t\ttable a\n\tWHERE\n\t\t(a.id=1)) t\n", $str);
	}

	public function testAlias() {
		$query = TestSelect::create()
		->from('t', 'travis#test1')
		->asString();

		$this->assertEquals("SELECT\n\t*\nFROM\n\ttravis_test.test1 t\n", $query);
	}

	public function testCount() {
		$query = TestSelect::create()
		->field('COUNT(*)')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10')
		->asString();

		$this->assertEquals("SELECT\n\tCOUNT(*)\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nWHERE\n\t(t1.id > 10)\n", $query);
	}

	public function testSubselectAsField() {
		$query = TestSelect::create()
		->field('COUNT(*)')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10');

		$query = TestSelect::create()
		->field($query, 'testfield')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10')
		->asString();

		$this->assertEquals("SELECT\n\t(\n\t\tSELECT\n\t\t\tCOUNT(*)\n\t\tFROM\n\t\t\ttest1 t1\n\t\tINNER JOIN\n\t\t\ttest2 t2 ON t1.id=t2.id\n\t\tWHERE\n\t\t\t(t1.id > 10)\n\t) AS `testfield`\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nWHERE\n\t(t1.id > 10)\n", $query);
	}

	public function testSubselectUnion() {
		$query = TestSelect::create()
		->field('t1.field')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10');

		$query = TestSelect::create()
		->union($query)
		->field('t1.field')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10')
		->asString();

		$this->assertEquals("SELECT\n\tt1.field\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nWHERE\n\t(t1.id > 10)\n\nUNION\nSELECT\n\tt1.field\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nWHERE\n\t(t1.id > 10)\n", $query);
	}
}
