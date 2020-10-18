<?php
namespace Kir\MySQL\Databases;

use Closure;
use Kir\MySQL\Tools\AliasRegistry;
use PDO;
use PDOException;

class TestDB extends MySQL {
	/** @var PDO */
	private $pdo = null;
	/** @var AliasRegistry */
	private $aliasRegistry;

	/**
	 * @param Closure $fn
	 * @return static
	 */
	public static function use(Closure $fn) {
		$inst = new static();
		try {
			$fn($inst);
		} finally {
			$inst->close();
		}
	}

	/**
	 */
	public function __construct() {
		parent::__construct($this->getPDO());
		$this->aliasRegistry = new AliasRegistry();
		$this->getAliasRegistry()->add('travis', 'travis_test.');
		$this->getAliasRegistry()->add('test', 'travis_test.');
	}

	public function close() {
		try {
			$this->pdo->exec('KILL CONNECTION_ID()');
		} catch(PDOException $e) {
			if((string) $e->getCode() !== '70100') {
				throw $e;
			}
		}
		$this->pdo = null;
	}

	public function getPDO(): PDO {
		$this->pdo = new PDO('mysql:host=localhost;charset=utf8', 'root', null, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]);
		return $this->pdo;
	}

	/**
	 */
	public function install() {
		$this->pdo->exec('CREATE DATABASE IF NOT EXISTS `travis_test`;');
		$this->pdo->exec('USE `travis_test`;');
		$this->pdo->exec('CREATE TABLE IF NOT EXISTS `test1` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `field1` INT(11) NULL DEFAULT NULL, `field2` DECIMAL(15,2) NULL DEFAULT NULL, `field3` DATETIME NULL DEFAULT NULL, `field4` VARCHAR(255) NULL DEFAULT NULL, PRIMARY KEY (`id`)) COLLATE=\'utf8_general_ci\' ENGINE=InnoDB;');
		$this->pdo->exec('CREATE TABLE IF NOT EXISTS `test2` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `field1` INT(11) NULL DEFAULT NULL, `field2` DECIMAL(15,2) NULL DEFAULT NULL, `field3` DATETIME NULL DEFAULT NULL, `field4` VARCHAR(255) NULL DEFAULT NULL, PRIMARY KEY (`id`)) COLLATE=\'utf8_general_ci\' ENGINE=InnoDB;');
		$stmt1 = $this->pdo->prepare('INSERT INTO test1 SET field1=:field1, field2=:field2, field3=:field3, field4=:field4;');
		$stmt2 = $this->pdo->prepare('INSERT INTO test2 SET field1=:field1, field2=:field2, field3=:field3, field4=:field4;');
		for($i=1; $i <= 100; $i++) {
			$stmt1->execute(['field1' => $i, 'field2' => $i / 10, 'field3' => '2000-01-01 00:00:00', 'field4' => "Test text #{$i}"]);
			$stmt2->execute(['field1' => $i, 'field2' => $i / 10, 'field3' => '2000-01-01 00:00:00', 'field4' => "Test text #{$i}"]);
		}
	}

	/**
	 */
	public function uninstall() {
		$this->pdo->exec('DROP DATABASE IF EXISTS `travis_test`;');
	}
}
