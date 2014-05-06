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
		$str = TestSelect::create()
		->field('a')
		->from('t1', 'test1')
		->{$method}('t2', 'test2', 't2.id=t1.id')
		->asString();
		$this->assertEquals("SELECT a FROM test1 t1 {$type} JOIN test2 t2 ON t2.id=t1.id ;", $str);

		$str = TestSelect::create()
		->field('a')
		->from('t1', 'test1')
		->{$method}('t2', 'test2', 't2.id=t1.id AND t2.id < ?', 1000)
		->asString();
		$this->assertEquals("SELECT a FROM test1 t1 {$type} JOIN test2 t2 ON t2.id=t1.id AND t2.id < 1000 ;", $str);
	}

	public function testWhere() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->where('a+1<2')
		->asString();
		$this->assertEquals('SELECT a FROM test t WHERE a+1<2 ;', $str);
	}

	public function testHaving() {
		$str = TestSelect::create()
		->field('a')
		->from('t', 'test')
		->having('a+1<2')
		->asString();
		$this->assertEquals('SELECT a FROM test t HAVING a+1<2 ;', $str);
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
}