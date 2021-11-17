<?php
namespace Kir\MySQL\Databases;

use Closure;
use Kir\MySQL\Tools\AliasRegistry;
use PDO;
use PDOException;

class TestDB extends MySQL {
	/** @var PDO|null */
	private $pdo;

	/**
	 * @template T
	 * @param callable(TestDB): T $fn
	 * @return T
	 */
	public static function use($fn) {
		$inst = new TestDB();
		try {
			return $fn($inst);
		} finally {
			$inst->close();
		}
	}

	/**
	 */
	final public function __construct() {
		parent::__construct($this->getPDO());
		$this->getAliasRegistry()->add('travis', 'travis_test.');
		$this->getAliasRegistry()->add('test', 'travis_test.');
	}

	public function close(): void {
		try {
			$this->getPDO()->exec('KILL CONNECTION_ID()');
		} catch(PDOException $e) {
			if((string) $e->getCode() !== '70100') {
				throw $e;
			}
		}
		$this->pdo = null;
	}

	public function getPDO(): PDO {
		if($this->pdo === null) {
			$dsn = $_ENV['DB_DNS'] ?? 'mysql:host=localhost;charset=utf8';
			$user = $_ENV['DB_USER'] ?? 'root';
			$pass = $_ENV['DB_PASS'] ?? null;
			$this->pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		}
		return $this->pdo;
	}

	/**
	 */
	public function install(): void {
		$pdo = $this->getPDO();
		$pdo->exec('CREATE DATABASE IF NOT EXISTS `travis_test`;');
		$pdo->exec('USE `travis_test`;');
		$pdo->exec('CREATE TABLE IF NOT EXISTS `test1` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `field1` INT(11) NULL DEFAULT NULL, `field2` DECIMAL(15,2) NULL DEFAULT NULL, `field3` DATETIME NULL DEFAULT NULL, `field4` VARCHAR(255) NULL DEFAULT NULL, PRIMARY KEY (`id`)) COLLATE=\'utf8_general_ci\' ENGINE=InnoDB;');
		$pdo->exec('CREATE TABLE IF NOT EXISTS `test2` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `field1` INT(11) NULL DEFAULT NULL, `field2` DECIMAL(15,2) NULL DEFAULT NULL, `field3` DATETIME NULL DEFAULT NULL, `field4` VARCHAR(255) NULL DEFAULT NULL, PRIMARY KEY (`id`)) COLLATE=\'utf8_general_ci\' ENGINE=InnoDB;');
		$stmt1 = $pdo->prepare('INSERT INTO test1 SET field1=:field1, field2=:field2, field3=:field3, field4=:field4;');
		$stmt2 = $pdo->prepare('INSERT INTO test2 SET field1=:field1, field2=:field2, field3=:field3, field4=:field4;');
		for($i=1; $i <= 100; $i++) {
			$stmt1->execute(['field1' => $i, 'field2' => $i / 10, 'field3' => '2000-01-01 00:00:00', 'field4' => "Test text #{$i}"]);
			$stmt2->execute(['field1' => $i, 'field2' => $i / 10, 'field3' => '2000-01-01 00:00:00', 'field4' => "Test text #{$i}"]);
		}
	}

	/**
	 */
	public function uninstall(): void {
		$this->getPDO()->exec('DROP DATABASE IF EXISTS `travis_test`;');
	}
}
