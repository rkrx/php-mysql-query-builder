<?php
namespace Kir\MySQL\Databases;

use Kir\MySQL\Builder;
use Kir\MySQL\Tools\AliasRegistry;

class TestDB extends MySQL {
	/**
	 * @var \PDO
	 */
	private $pdo = null;
	/**
	 * @var AliasRegistry
	 */
	private $aliasRegistry;

	/**
	 * @return static
	 */
	public static function create() {
		return new static();
	}

	/**
	 */
	function __construct() {
		static $pdo = null;
		if($pdo === null) {
			$pdo = new \PDO('mysql:host=localhost;charset=utf8', 'root');
			$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		$this->pdo = $pdo;
		parent::__construct($this->pdo);
		$this->aliasRegistry = new AliasRegistry();
		$this->getAliasRegistry()->add('travis', 'travis_test.');
	}

	/**
	 */
	public function install() {
		$this->pdo->exec('DROP DATABASE IF EXISTS `travis_test`;');
		$this->pdo->exec('CREATE DATABASE `travis_test`;');
		$this->pdo->exec('USE `travis_test`;');
		$this->pdo->exec('CREATE TABLE `test1` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `field1` INT(11) NULL DEFAULT NULL, `field2` DECIMAL(15,2) NULL DEFAULT NULL, `field3` DATETIME NULL DEFAULT NULL, `field4` VARCHAR(255) NULL DEFAULT NULL, PRIMARY KEY (`id`)) COLLATE=\'utf8_general_ci\' ENGINE=InnoDB;');
		$this->pdo->exec('CREATE TABLE `test2` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `field1` INT(11) NULL DEFAULT NULL, `field2` DECIMAL(15,2) NULL DEFAULT NULL, `field3` DATETIME NULL DEFAULT NULL, `field4` VARCHAR(255) NULL DEFAULT NULL, PRIMARY KEY (`id`)) COLLATE=\'utf8_general_ci\' ENGINE=InnoDB;');
		$stmt1 = $this->pdo->prepare('INSERT INTO test1 SET field1=:field1, field2=:field2, field3=:field3, field4=:field4;');
		$stmt2 = $this->pdo->prepare('INSERT INTO test2 SET field1=:field1, field2=:field2, field3=:field3, field4=:field4;');
		for($i=1; $i <= 100; $i++) {
			$stmt1->execute(array('field1' => $i, 'field2' => $i / 10, 'field3' => '2000-01-01 00:00:00', 'field4' => "Test text #{$i}"));
			$stmt2->execute(array('field1' => $i, 'field2' => $i / 10, 'field3' => '2000-01-01 00:00:00', 'field4' => "Test text #{$i}"));
		}
	}

	/**
	 */
	public function uninstall() {
		#$this->pdo->exec('DROP DATABASE IF EXISTS `travis_test`;');
	}
}