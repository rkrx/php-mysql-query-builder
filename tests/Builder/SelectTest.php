<?php
namespace Kir\MySQL\Builder;

use Kir\MySQL\Builder\SelectTest\TestSelect;

class SelectTest extends \PHPUnit_Framework_TestCase {
	public function testAddition() {
		$str = TestSelect::create()->field('1+2')->asString();
		$this->assertEquals('SELECT 1+2 ;', $str);
	}

	public function testFrom() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->asString();
		$this->assertEquals('SELECT a FROM test t ;', $str);
	}

	public function testMultipleFrom() {
		$str = TestSelect::create()
		->field('a')
		->from('t1', 'test1')
		->from('t2', 'test2')
		->asString();
		$this->assertEquals('SELECT a FROM test1 t1, test2 t2 ;', $str);
	}

	public function testJoins() {
		$this->_testJoin('joinInner', 'INNER');
		$this->_testJoin('joinLeft', 'LEFT');
		$this->_testJoin('joinRight', 'RIGHT');
	}

	private function _testJoin($method, $type) {
		$sql = TestSelect::create()
		->field('a')
		->from('t1', 'test1')
		->{$method}('t2', 'test2', 't2.id=t1.id')
		->asString();
		$this->assertEquals("SELECT a FROM test1 t1 {$type} JOIN test2 t2 ON t2.id=t1.id ;", $sql);

		$sql = TestSelect::create()
		->field('a')
		->from('t1', 'test1')
		->{$method}('t2', 'test2', 't2.id=t1.id AND t2.id < ?', 1000)
		->asString();
		$this->assertEquals("SELECT a FROM test1 t1 {$type} JOIN test2 t2 ON t2.id=t1.id AND t2.id < '1000' ;", $sql);
	}

	public function testWhere() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a+1<2')
		->asString();
		$this->assertEquals('SELECT a FROM test t WHERE a+1<2 ;', $str);

		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a < ?', 1000)
		->asString();
		$this->assertEquals("SELECT a FROM test t WHERE a < '1000' ;", $str);

		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a < :0', 1000)
		->asString();
		$this->assertEquals("SELECT a FROM test t WHERE a < '1000' ;", $str);
	}

	public function testHaving() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->having('a+1<2')
		->asString();
		$this->assertEquals('SELECT a FROM test t HAVING a+1<2 ;', $str);
	}

	public function testOrder() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->orderBy('a', 'desc')
		->asString();
		$this->assertEquals('SELECT a FROM test t ORDER BY a DESC ;', $str);
	}

	public function testGroup() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->groupBy('a', 'b', 'c')
		->asString();
		$this->assertEquals('SELECT a FROM test t GROUP BY a, b, c ;', $str);
	}

	public function testLimit() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->asString();
		$this->assertEquals('SELECT a FROM test t LIMIT 100 ;', $str);
	}

	public function testOffset() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->limit(100)
		->offset(50)
		->asString();
		$this->assertEquals('SELECT a FROM test t LIMIT 100 OFFSET 50 ;', $str);

		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->offset(100)
		->asString();
		$this->assertEquals('SELECT a FROM test t ;', $str);
	}

	public function testForUpdate() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->forUpdate()
		->asString();
		$this->assertEquals('SELECT a FROM test t FOR UPDATE ;', $str);
	}

	public function testInnerSelect() {
		$select = TestSelect::create()
		->from('a', 'table')
		->where('a.id=1');

		$str = (string) TestSelect::create()
		->from('t', $select)
		->asString();

		$this->assertEquals('SELECT * FROM (SELECT * FROM table a WHERE a.id=1) t ;', $str);
	}
}