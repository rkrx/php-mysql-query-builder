<?php
namespace Kir\MySQL\Common;

use Closure;
use Kir\MySQL\Builder\DeleteTest\TestDelete;
use Kir\MySQL\Builder\InsertTest\TestInsert;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Builder\UpdateTest\TestUpdate;
use Kir\MySQL\Databases\TestDB;
use PHPUnit\Framework\TestCase;

class DBTestCase extends TestCase {
	/** @var TestDB */
	private $db;

	public static function setUpBeforeClass(): void {
		TestDB::use(function (TestDB $db) {
			$db->install();
		});
	}

	public static function tearDownAfterClass(): void {
		TestDB::use(function (TestDB $db) {
			$db->uninstall();
		});
	}

	protected function use(Closure $fn) {
		TestDB::use($fn);
	}

	protected function setUp() {
		parent::setUp();
		$this->db = new TestDB();
		$this->db->exec('USE travis_test');
	}

	protected function tearDown() {
		parent::tearDown();
		$this->db->close();
		$this->db = null;
	}

	protected function getDB(): TestDB {
		return $this->db;
	}

	protected function select() {
		return new TestSelect($this->db);
	}

	protected function insert(): TestInsert {
		return new TestInsert($this->db);
	}

	protected function update(): TestUpdate {
		return new TestUpdate($this->db);
	}

	protected function delete(): TestDelete {
		return new TestDelete($this->db);
	}
}
