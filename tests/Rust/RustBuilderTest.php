<?php
namespace Kir\MySQL\Rust;

use Kir\MySQL\Builder\DBExpr;
use Kir\MySQL\Databases\RustMySQL;
use PDO;
use PHPUnit\Framework\TestCase;

class RustBuilderTest extends TestCase {
	protected function setUp(): void {
		if(!class_exists('Kir\\MySQL\\Rust\\NativeSelect', false)) {
			self::markTestSkipped('Native Rust query-builder extension is not loaded');
		}
		if(!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			self::markTestSkipped('pdo_sqlite is required for adapter-only smoke tests');
		}
	}

	private function db(): RustMySQL {
		return new RustMySQL(new PDO('sqlite::memory:'));
	}

	public function testRustSelectRendering(): void {
		$sql = (string) $this->db()
			->select()
			->field('a')
			->from('t', 'test')
			->where('a < ?', 1000);

		self::assertEquals("SELECT\n\ta\nFROM\n\ttest t\nWHERE\n\t(a < 1000)\n", $sql);
	}

	public function testRustInsertRendering(): void {
		$sql = (string) $this->db()
			->insert()
			->into('test1')
			->add('field1', 123)
			->update('field2', new DBExpr('NOW()'));

		self::assertEquals("INSERT INTO\n\ttest1\nSET\n\t`field1`=123\nON DUPLICATE KEY UPDATE\n\t`field2`=NOW()\n", $sql);
	}

	public function testRustUpdateRendering(): void {
		$sql = (string) $this->db()
			->update()
			->table('t1', 'test1')
			->joinInner('t2', 'test2', 't1.field1 = t2.field1')
			->set('t1.field1', 1);

		self::assertEquals("UPDATE\n\ttest1 t1\nINNER JOIN\n\ttest2 t2 ON t1.field1 = t2.field1\nSET\n\t`t1`.`field1`=1\n", $sql);
	}

	public function testRustDeleteRendering(): void {
		$sql = (string) $this->db()
			->delete()
			->from('t', 'test1')
			->where('t.a = 1');

		self::assertEquals("DELETE t FROM\n\ttest1 t\nWHERE\n\t(t.a = 1)\n", $sql);
	}
}
