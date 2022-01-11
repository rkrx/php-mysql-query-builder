<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\Expr\DBExprFilter;
use Kir\MySQL\Builder\Expr\DBSortAliasNotFoundException;
use Kir\MySQL\Builder\Expr\OptionalDBFilterMap;
use Kir\MySQL\Builder\Expr\DBOrderSpec;
use Kir\MySQL\Builder\Expr\RequiredDBFilterMap;
use Kir\MySQL\Builder\Expr\RequiredValueNotFoundException;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Builder\SelectTest\TestSelectMySQL;
use Kir\MySQL\Builder\Value\DBOptionalValue;
use Kir\MySQL\Common\DBTestCase;
use Kir\MySQL\Databases\TestDB;
use Kir\MySQL\Tools\VirtualTable;

class SelectTest extends DBTestCase {
	public function testAddition(): void {
		$str = $this->select()
		->field('1+2')
		->asString();
		self::assertEquals("SELECT\n\t1+2\n", $str);
	}

	public function testFrom(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
	}

	public function testFromArray(): void {
		$str = $this->select()
		->field('a.a')
		->field('b.b')
		->from('a', [['a' => 1, 'b' => 3], ['a' => 2, 'b' => 2], ['a' => 3, 'b' => 1]])
		->joinInner('b', [['a' => 1, 'b' => 3], ['a' => 2, 'b' => 2], ['a' => 3, 'b' => 1]])
		->asString();
		self::assertEquals("SELECT\n\ta.a,\n\tb.b\nFROM\n\t(SELECT 1 AS `a`, 3 AS `b`\n\tUNION\n\tSELECT 2 AS `a`, 2 AS `b`\n\tUNION\n\tSELECT 3 AS `a`, 1 AS `b`) a\nINNER JOIN\n\t(SELECT 1 AS `a`, 3 AS `b`\n\tUNION\n\tSELECT 2 AS `a`, 2 AS `b`\n\tUNION\n\tSELECT 3 AS `a`, 1 AS `b`) b\n", $str);
	}

	public function testMultipleFrom(): void {
		$str = $this->select()
		->field('a')
		->from('t1', 'test1')
		->from('t2', 'test2')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1,\n\ttest2 t2\n", $str);
	}

	public function testLeftJoin(): void {
		$sql = $this->select()
		->field('a')
		->from('t1', 'test1')
		->joinLeft('t2', 'test2', 't2.id=t1.id')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\nLEFT JOIN\n\ttest2 t2 ON t2.id=t1.id\n", $sql);

		$sql = $this->select()
		->field('a')
		->from('t1', 'test1')
		->joinLeft('t2', 'test2', 't2.id=t1.id AND t2.id < ?', 1000)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\nLEFT JOIN\n\ttest2 t2 ON t2.id=t1.id AND t2.id < 1000\n", $sql);
	}

	public function testRightJoin(): void {
		$sql = $this->select()
		->field('a')
		->from('t1', 'test1')
		->joinRight('t2', 'test2', 't2.id=t1.id')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\nRIGHT JOIN\n\ttest2 t2 ON t2.id=t1.id\n", $sql);

		$sql = $this->select()
		->field('a')
		->from('t1', 'test1')
		->joinRight('t2', 'test2', 't2.id=t1.id AND t2.id < ?', 1000)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\nRIGHT JOIN\n\ttest2 t2 ON t2.id=t1.id AND t2.id < 1000\n", $sql);
	}

	public function testInnerJoin(): void {
		$sql = $this->select()
		->field('a')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't2.id=t1.id')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t2.id=t1.id\n", $sql);

		$sql = $this->select()
		->field('a')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't2.id=t1.id AND t2.id < ?', 1000)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t2.id=t1.id AND t2.id < 1000\n", $sql);
	}

	public function testWhere(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where('a+1<2')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a+1<2)\n", $str);

		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where('a < ?', 1000)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a < 1000)\n", $str);
	}

	public function testWhereAsObject(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where((object) ['field1' => 1, 't.field2' => 'aaa', '`t`.`field3`' => null])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(`field1`=1)\n\tAND\n\t(`t`.`field2`='aaa')\n\tAND\n\t(ISNULL(`t`.`field3`))\n", $str);
	}

	public function testWhereAsEmptyObject(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where((object) [])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
	}

	public function testWhereAsArray(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where(['field1' => 1, 't.field2' => 'aaa', '`t`.`field3`' => null])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(`field1`=1)\n\tAND\n\t(`t`.`field2`='aaa')\n\tAND\n\t(ISNULL(`t`.`field3`))\n", $str);
	}

	public function testWhereAsEmptyArray(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where([])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
	}

	public function testHaving(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->having('a+1<2')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nHAVING\n\t(a+1<2)\n", $str);
	}

	public function testHavingAsObject(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->having((object) ['field1' => 1, 'field2' => 'aaa', 'field3' => null])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nHAVING\n\t(`field1`=1)\n\tAND\n\t(`field2`='aaa')\n\tAND\n\t(ISNULL(`field3`))\n", $str);
	}

	public function testHavingAsEmptyObject(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->having((object) [])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
	}

	public function testHavingAsArray(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->having(['field1' => 1, 'field2' => 'aaa', 'field3' => null])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nHAVING\n\t(`field1`=1)\n\tAND\n\t(`field2`='aaa')\n\tAND\n\t(ISNULL(`field3`))\n", $str);
	}

	public function testHavingAsEmptyArray(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->having([])
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
	}

	public function testDBExpr(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where('a=?', new DBExpr('NOW()'))
		->having('b=?', new DBExpr('NOW()'))
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a=NOW())\nHAVING\n\t(b=NOW())\n", $str);

		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where('a < ?', 1000)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a < 1000)\n", $str);
	}

	public function testDBExprFilter(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->where(new DBExprFilter('a=? AND b=?', ['x' => ['y' => 1]], 'x.y'))
		->having(new DBExprFilter('a=? AND b=?', ['x' => ['y' => 1]], 'x.y'))
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a=1 AND b=1)\nHAVING\n\t(a=1 AND b=1)\n", $str);
	}

	public function testOrder(): void {
		$select = $this->select()
		->field('a')
		->from('t', 'test')
		->orderBy('a', 'DESC');
		$str = $select->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nORDER BY\n\ta DESC\n", $str);
		self::assertEquals([['a', 'DESC']], $select->getOrderBy());
		$select->resetOrderBy();
		$str = $select->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
		self::assertEquals([], $select->getOrderBy());
	}

	public function testGroup(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->groupBy('a', 'b', 'c')
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nGROUP BY\n\ta,\n\tb,\n\tc\n", $str);
	}

	public function testLimit(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t100\n", $str);
	}

	public function testOptionalLimit(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->limit(new DBOptionalValue(['x' => 100], 'x'))
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t100\n", $str);

		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->limit(new DBOptionalValue(['x' => 100], 'y'))
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);

		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->limit(new DBOptionalValue(['x' => 100], 'y'))
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\n", $str);
	}

	public function testOffset(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->offset(50)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t100\nOFFSET\n\t50\n", $str);
	}

	public function testOffsetWithoutLimit(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->offset(50)
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t18446744073709551615\nOFFSET\n\t50\n", $str);
	}

	public function testOptionalOffset(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->offset(new DBOptionalValue(['a' => ['b' => ['c' => 50]]], 'a.b.c'))
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t100\nOFFSET\n\t50\n", $str);

		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->offset(new DBOptionalValue(['a' => ['b' => ['c' => 50]]], 'a.b.d'))
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nLIMIT\n\t100\n", $str);
	}

	public function testForUpdate(): void {
		$str = $this->select()
		->field('a')
		->from('t', 'test')
		->forUpdate()
		->asString();
		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nFOR UPDATE\n", $str);
	}

	public function testInnerSelect(): void {
		$select = $this->select()
		->from('a', 'table')
		->where('a.id=1');

		$str = (string) $this->select()
		->from('t', $select)
		->asString();

		self::assertEquals("SELECT\n\t*\nFROM\n\t(SELECT\n\t\t*\n\tFROM\n\t\ttable a\n\tWHERE\n\t\t(a.id=1)) t\n", $str);
	}

	public function testAlias(): void {
		$query = $this->select()
		->from('t', 'travis#test1')
		->asString();

		self::assertEquals("SELECT\n\t*\nFROM\n\ttravis_test.test1 t\n", $query);
	}

	public function testCount(): void {
		$query = $this->select()
		->field('COUNT(*)')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10')
		->asString();

		self::assertEquals("SELECT\n\tCOUNT(*)\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nWHERE\n\t(t1.id > 10)\n", $query);
	}

	public function testSubselectAsField(): void {
		$query = $this->select()
		->field('COUNT(*)')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10');

		$query = $this->select()
		->field($query, 'testfield')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10')
		->asString();

		self::assertEquals("SELECT\n\t(\n\t\tSELECT\n\t\t\tCOUNT(*)\n\t\tFROM\n\t\t\ttest1 t1\n\t\tINNER JOIN\n\t\t\ttest2 t2 ON t1.id=t2.id\n\t\tWHERE\n\t\t\t(t1.id > 10)\n\t) AS `testfield`\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nWHERE\n\t(t1.id > 10)\n", $query);
	}

	public function testSubselectUnion(): void {
		$query = $this->select()
		->field('t1.field')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10');

		$query = $this->select()
		->union($query)
		->field('t1.field')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->where('t1.id > 10')
		->asString();

		self::assertEquals("(\n\tSELECT\n\t\tt1.field\n\tFROM\n\t\ttest1 t1\n\tINNER JOIN\n\t\ttest2 t2 ON t1.id=t2.id\n\tWHERE\n\t\t(t1.id > 10)\n) UNION (\n\tSELECT\n\t\tt1.field\n\tFROM\n\t\ttest1 t1\n\tINNER JOIN\n\t\ttest2 t2 ON t1.id=t2.id\n\tWHERE\n\t\t(t1.id > 10)\n)", $query);
	}

	public function testOrderByValues(): void {
		$query = $this->select()
		->field('t1.field')
		->from('t1', 'test1')
		->joinInner('t2', 'test2', 't1.id=t2.id')
		->orderByValues('t1.field', [5, 1, 66, 183, 99, 2, 6])
		->asString();

		self::assertEquals("SELECT\n\tt1.field\nFROM\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.id=t2.id\nORDER BY\n\tCASE `t1`.`field`\n\t\tWHEN 5 THEN 0\n\t\tWHEN 1 THEN 1\n\t\tWHEN 66 THEN 2\n\t\tWHEN 183 THEN 3\n\t\tWHEN 99 THEN 4\n\t\tWHEN 2 THEN 5\n\t\tWHEN 6 THEN 6\n\tEND ASC\n", $query);
	}

	public function testDistinct(): void {
		$query = $this->select()
		->distinct()
		->field('t1.field1')
		->field('t1.field2')
		->from('t1', 'test1')
		->asString();

		self::assertEquals("SELECT DISTINCT\n\tt1.field1,\n\tt1.field2\nFROM\n\ttest1 t1\n", $query);
	}

	public function testOptionalExpressions(): void {
		$filter = ['filter' => ['name' => 'aaa', 'ids' => [1, 2, 3], 'empty' => [null, '', ['']]]];
		$opt = new OptionalDBFilterMap($filter);

		$query = $this->select()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', ['filter', 'name']))
		->asString();

		self::assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field='aaa')\n", $query);

		$query = $this->select()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', 'filter.name'))
		->asString();

		self::assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field='aaa')\n", $query);

		$query = $this->select()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', ['filter', 'age']))
		->asString();

		self::assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\n", $query);

		$query = $this->select()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field IN (?)', ['filter', 'ids']))
		->asString();

		self::assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field IN (1, 2, 3))\n", $query);

		$filter = ['filter' => ['name' => 'aaa', 'ids' => [1, 2, 3]]];
		$opt = new OptionalDBFilterMap($filter);

		$query = $this->select()
		->field('t.field')
		->from('t', 'test')
		->where($opt('t.field=?', ['filter', 'empty']))
		->asString();

		self::assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\n", $query);
	}

	public function testRequiredExpression(): void {
		$filter = ['filter' => ['name' => 'aaa']];
		$req = new RequiredDBFilterMap($filter);

		$query = $this->select()
		->field('t.field')
		->from('t', 'test')
		->where($req('t.field=?', ['filter', 'name']))
		->asString();

		self::assertEquals("SELECT\n\tt.field\nFROM\n\ttest t\nWHERE\n\t(t.field='aaa')\n", $query);
		$this->expectException(RequiredValueNotFoundException::class);

		$this->select()
		->field('t.field')
		->from('t', 'test')
		->where($req('t.field=?', 'filter.id'))
		->asString();
	}

	public function testSortSpecification(): void {
		$query = $this->select()
		->field('t.field1')
		->field('t.field2')
		->from('t', 'test')
		->orderBy(new DBOrderSpec(['field1' => 't.field1', 'field2' => 'REVERSE(t.field2)', 'field3' => 't.field2'], ['field3' => 'ASC', 'field1' => 'ASC', 'field2' => 'ASC']))
		->asString();

		self::assertEquals("SELECT\n\tt.field1,\n\tt.field2\nFROM\n\ttest t\nORDER BY\n\tt.field2 ASC,\n\tt.field1 ASC,\n\tREVERSE(t.field2) ASC\n", $query);

		$this->expectException(DBSortAliasNotFoundException::class);

		$query = $this->select()
		->field('t.field1')
		->field('t.field2')
		->from('t', 'test')
		->orderBy(new DBOrderSpec(['field1' => 't.field1', 'field2' => 'REVERSE(t.field2)', 'field4' => 't.field2'], ['field3' => 'ASC', 'field1' => 'ASC', 'field2' => 'ASC', 'field4' => 'ASC']))
		->asString();
	}

	public function testVirtualTables(): void {
		$vt1 = $this->select()
		->field('a.field1')
		->from('a', 'tableA');

		$db = new TestDB();
		$db->getVirtualTables()->add('virt_table1', $vt1);
		$db->getVirtualTables()->add('virt_table2', function () {
			return $this->select()
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

		self::assertEquals("SELECT\n\tt.field1,\n\tt.field2\nFROM\n\ttest t\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a) vt1 ON vt1.field1=t.field1\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a) vt2 ON vt2.field2=t.field2\n", $query);
	}

	public function testParametrizedVirtualTables(): void {
		$vt1 = $this->select()
		->field('a.field1')
		->from('a', 'tableA');

		$db = new TestDB();
		$db->getVirtualTables()->add('virt_table1', $vt1);
		$db->getVirtualTables()->add('virt_table2', function (array $args) {
			return $this->select()
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

		self::assertEquals("SELECT\n\tt.field1,\n\tt.field2\nFROM\n\ttest t\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a) vt1 ON vt1.field1=t.field1\nINNER JOIN\n\t(SELECT\n\t\ta.field1\n\tFROM\n\t\ttableA a\n\tWHERE\n\t\t(a.active=1)) vt2 ON vt2.field2=t.field2\n", $query);
	}

	public function testArrayTables(): void {
		$vt1 = $this->select()
		->field('a.value')
		->from('a', range(1, 9))
		->asString();

		self::assertEquals("SELECT\n\ta.value\nFROM\n\t(SELECT 1 AS `value`\n\tUNION\n\tSELECT 2 AS `value`\n\tUNION\n\tSELECT 3 AS `value`\n\tUNION\n\tSELECT 4 AS `value`\n\tUNION\n\tSELECT 5 AS `value`\n\tUNION\n\tSELECT 6 AS `value`\n\tUNION\n\tSELECT 7 AS `value`\n\tUNION\n\tSELECT 8 AS `value`\n\tUNION\n\tSELECT 9 AS `value`) a\n", $vt1);
	}
}
