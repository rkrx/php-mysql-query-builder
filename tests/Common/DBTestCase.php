<?php
namespace Kir\MySQL\Common;

use Kir\MySQL\Builder\DeleteTest\TestDelete;
use Kir\MySQL\Builder\InsertTest\TestInsert;
use Kir\MySQL\Builder\SelectTest\TestSelect;
use Kir\MySQL\Builder\UpdateTest\TestUpdate;
use Kir\MySQL\Databases\TestDB;
use PHPUnit\Framework\TestCase;

class DBTestCase extends TestCase {
	/** @var TestDB|null */
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

	/**
	 * @template T
	 * @param callable(TestDB): T $fn
	 * @return T
	 */
	protected function use(callable $fn) {
		return TestDB::use($fn);
	}

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		if($this->db !== null) {
			$this->db->close();
			$this->db = null;
		}
	}

	protected function getDB(): TestDB {
		if($this->db === null) {
			$this->db = new TestDB();
			$this->db->exec('USE travis_test');
		}
		return $this->db;
	}

	/**
	 * @return TestSelect
	 */
	protected function select(): TestSelect {
		return new TestSelect($this->getDB());
	}

	/**
	 * @return TestInsert
	 */
	protected function insert(): TestInsert {
		return new TestInsert($this->getDB());
	}

	/**
	 * @return TestUpdate
	 */
	protected function update(): TestUpdate {
		return new TestUpdate($this->getDB());
	}

	/**
	 * @return TestDelete
	 */
	protected function delete(): TestDelete {
		return new TestDelete($this->getDB());
	}
}
