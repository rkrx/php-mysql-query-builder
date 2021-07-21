<?php

namespace Kir\MySQL\Builder\Helpers;

use PHPUnit\Framework\TestCase;

class RecursiveStructureAccessTest extends TestCase {
	public function testRecursiveHas(): void {
		$array = ['a' => ['b' => ['c' => 123]]];

		$result = RecursiveStructureAccess::recursiveHas($array, ['a', 'b', 'c']);
		self::assertTrue($result);

		$result = RecursiveStructureAccess::recursiveHas($array, ['a', 'b', 'd']);
		self::assertFalse($result);

		$result = RecursiveStructureAccess::recursiveHas($array, 'a.b.c');
		self::assertTrue($result);

		$result = RecursiveStructureAccess::recursiveHas($array, 'a.b.d');
		self::assertFalse($result);
	}

	public function testRecursiveGet(): void {
		$array = ['a' => ['b' => ['c' => 123]]];

		$result = RecursiveStructureAccess::recursiveGet($array, ['a', 'b', 'c'], null);
		self::assertEquals(123, $result);

		$result = RecursiveStructureAccess::recursiveGet($array, ['a', 'b', 'd'], 456);
		self::assertEquals(456, $result);

		$result = RecursiveStructureAccess::recursiveGet($array, 'a.b.c', null);
		self::assertEquals(123, $result);

		$result = RecursiveStructureAccess::recursiveGet($array, 'a.b.d', 456);
		self::assertEquals(456, $result);
	}
}
