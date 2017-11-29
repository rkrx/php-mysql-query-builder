<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Expr\DBExprFilter;
use Kir\MySQL\Builder\Expr\DBExprOrderBySpec;
use Kir\MySQL\Builder\Expr\OptionalDBFilterMap;
use Kir\MySQL\Builder\Expr\RequiredDBFilterMap;
use Kir\MySQL\Builder\Expr\RequiredValueNotFoundException;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Databases\TestDB;
use Kir\MySQL\Tools\VirtualTable;

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

	public function testFromArray() {
		$str = TestSelect::create()
		->field('a.a')
		->field('b.b')
		->from('a', [['a' => 1, 'b' => 3], ['a' => 2, 'b' => 2], ['a' => 3, 'b' => 1]])
		->joinInner('b', [['a' => 1, 'b' => 3], ['a' => 2, 'b' => 2], ['a' => 3, 'b' => 1]])
		->asString();
		$this->assertEquals("SELECT\n\ta.a,\n\tb.b\nFROM\n\t(SELECT '1' AS `a`, '3' AS `b`\n\tUNION\n\tSELECT '2' AS `a`, '2' AS `b`\n\tUNION\n\tSELECT '3' AS `a`, '1' AS `b`) a\nINNER JOIN\n\t(SELECT '1' AS `a`, '3' AS `b`\n\tUNION\n\tSELECT '2' AS `a`, '2' AS `b`\n\tUNION\n\tSELECT '3' AS `a`, '1' AS `b`) b\n", $str);
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
	}

	public function testWhereAsArray() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where(['field1' => 1, 'field2' => 'aaa'])
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(`field1`='1')\n\tAND\n\t(`field2`='aaa')\n", $str);
	}

	public function testHaving() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->having('a+1<2')
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nHAVING\n\t(a+1<2)\n", $str);

		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->having(['field1' => 1, 'field2' => 'aaa'])
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nHAVING\n\t(`field1`='1')\n\tAND\n\t(`field2`='aaa')\n", $str);
	}

	public function testDBExpr() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a=?', new DBExpr('NOW()'))
		->having('b=?', new DBExpr('NOW()'))
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a=NOW())\nHAVING\n\t(b=NOW())\n", $str);

		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a < ?', 1000)
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a < '1000')\n", $str);
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

	public function testOffsetWithoutLimit() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->offset(50)
		->asString();
		$this->assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t18446744073709551615\nOFFSET\n\t50\n", $str);
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

		$this->assertEquals("(\n\tSELECT\n\t\tt1.field\n\tFROM\n\t\ttest1 t1\n\tINNER JOIN\n\t\ttest2 t2 ON t1.id=t2.id\n\tWHERE\n\t\t(t1.id > 10)\n) UNION (\n\tSELECT\n\t\tt1.field\n\tFROM\n\t\ttest1 t1\n\tINNER JOIN\n\t\ttest2 t2 ON t1.id=t2.id\n\tWHERE\n\t\t(t1.id > 10)\n)", $query);
	}

	public function testOrderByValues() {
		$query = TestSelect::create()
		->field('t1.field')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->orderByValues('t1.field', [5, 1, 66, 183, 99, 2, 6])
		->asString();

		$this->assertEquals("SELECT\n\tt1.field\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nORDER BY\n\tCASE `t1`.`field`\n\t\tWHEN '5' THEN '0'\n\t\tWHEN '1' THEN '1'\n\t\tWHEN '66' THEN '2'\n\t\tWHEN '183' THEN '3'\n\t\tWHEN '99' THEN '4'\n\t\tWHEN '2' THEN '5'\n\t\tWHEN '6' THEN '6'\n\tEND ASC\n", $query);
	}

	public function testDistinct() {
		$query = TestSelect::create()
		->distinct()
		->field('t1.field1')
		->field('t1.field2')
		->from('t1', 'test1')
		->asString();

		$this->assertEquals("SELECT DISTINCT\n\tt1.field1,\n\tt1.field2\nFROM\n\ttest1 t1\n", $query);
	}

	public function testOptionalExpressions() {
		$filter = ['filter' => ['name' => 'aaa', 'ids' => [1, 2, 3], 'empty' => [null, '', ['']]]];
		$opt = new OptionalDBFilterMap($filter);

		$query = TestSelect::create()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', ['filter', 'name']))
		->asString();

		$this->assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field='aaa')\n", $query);

		$query = TestSelect::create()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', 'filter.name'))
		->asString();

		$this->assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field='aaa')\n", $query);

		$query = TestSelect::create()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', ['filter', 'age']))
		->asString();

		$this->assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\n", $query);

		$query = TestSelect::create()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field IN (?)', ['filter', 'ids']))
		->asString();

		$this->assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field IN ('1', '2', '3'))\n", $query);

		$query = TestSelect::create()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', ['filter', 'empty']))
		->asString();

		$this->assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\n", $query);
	}

	public function testRequiredExpression() {
		$filter = ['filter' => ['name' => 'aaa']];
		$req = new RequiredDBFilterMap($filter);

		$query = TestSelect::create()
		->field('t.field')
		->from('t', 'test')
		->where($req('t.field=?', ['filter', 'name']))
		->asString();

		$this->assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field='aaa')\n", $query);

		$this->setExpectedException('Kir\\MySQL\\Builder\\Expr\\RequiredValueNotFoundException');

		TestSelect::create()
		->field('t.field')
		->from('t', 'test')
		->where($req('t.field=?', 'filter.id'))
		->asString();
	}

	public function testSortSpecification() {
		$query = TestSelect::create()
		->field('t.field1')
		->field('t.field2')
		->from('t', 'test')
		->orderBy(new DBExprOrderBySpec(['field1', 'field2' => 'REVERSE(t.field2)'], [['field2', 'ASC'], ['field1', 'DESC'], ['field3' => 'ASC']]))
		->asString();

		$this->assertEquals("SELECT\n\tt.field1,\n\tt.field2\nFROM\n\ttest t\nORDER BY\n\tREVERSE(t.field2) ASC,\n\tfield1 DESC\n", $query);
	}

	public function testVirtualTables() {
		$vt1 = TestSelect::create()
		->field('a.field1')
		->from('a', 'tableA');
		
		$db = new TestDB();
		$db->getVirtualTables()->add('virt_table1', $vt1);
		$db->getVirtualTables()->add('virt_table2', function () {
			return TestSelect::create()
			->field('a.field1')
			->from('a', 'tableA');
		});
		
		$query = TestSelect::create($db)
		->field('t.field1')
		->field('t.field2')
		->from('t', 'test')
		->joinInner('vt1', 'virt_table1', 'vt1.field1=t.field1')
		->joinInner('vt2', 'virt_table2', 'vt2.field2=t.field2')
		->asString();

		$this->assertEquals("SELECT\n\tt.field1,\n\tt.field2\nFROM\n\ttest t\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a) vt1 ON vt1.field1=t.field1\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a) vt2 ON vt2.field2=t.field2\n", $query);
	}

	public function testParametrizedVirtualTables() {
		$vt1 = TestSelect::create()
		->field('a.field1')
		->from('a', 'tableA');
		
		$db = new TestDB();
		$db->getVirtualTables()->add('virt_table1', $vt1);
		$db->getVirtualTables()->add('virt_table2', function (array $args) {
			return TestSelect::create()
			->field('a.field1')
			->from('a', 'tableA')
			->where(new DBExprFilter('a.active=?', $args, 'active'));
		});
		
		$query = TestSelect::create($db)
		->field('t.field1')
		->field('t.field2')
		->from('t', 'test')
		->joinInner('vt1', 'virt_table1', 'vt1.field1=t.field1')
		->joinInner('vt2', new VirtualTable('virt_table2', ['active' => true]), 'vt2.field2=t.field2')
		->asString();

		$this->assertEquals("SELECT\n\tt.field1,\n\tt.field2\nFROM\n\ttest t\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a) vt1 ON vt1.field1=t.field1\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a\n\tWHERE\n\t\t(a.active='1')) vt2 ON vt2.field2=t.field2\n", $query);
	}

	public function testArrayTables() {
		$vt1 = TestSelect::create()
		->field('a.field1')
		->from('a', range(1, 9))->debug()
		->asString();
		
		$this->assertEquals("SELECT\n\ta.field1\nFROM\n\t(SELECT '1'\n\tUNION\n\tSELECT '2'\n\tUNION\n\tSELECT '3'\n\tUNION\n\tSELECT '4'\n\tUNION\n\tSELECT '5'\n\tUNION\n\tSELECT '6'\n\tUNION\n\tSELECT '7'\n\tUNION\n\tSELECT '8'\n\tUNION\n\tSELECT '9') a\n", $vt1);
	}
}
