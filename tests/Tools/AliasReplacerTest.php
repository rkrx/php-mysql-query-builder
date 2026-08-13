<?php

namespace Kir\MySQL\Tools;

use PHPUnit\Framework\TestCase;

class AliasReplacerTest extends TestCase {
	public function testReplaceQueryReplacesRegisteredAliasesInUnquotedParts(): void {
		$registry = new AliasRegistry();
		$registry->add('shop', '`dv-shop`.shop__');
		$replacer = new AliasReplacer($registry);

		$this->assertSame(
			'SELECT * FROM `dv-shop`.shop__products p JOIN `dv-shop`.shop__categories c ON c.id = p.category_id',
			$replacer->replaceQuery('SELECT * FROM shop#products p JOIN shop#categories c ON c.id = p.category_id'),
		);
	}

	public function testReplaceQueryProtectsQuotedParts(): void {
		$registry = new AliasRegistry();
		$registry->add('shop', 'shop__');
		$replacer = new AliasReplacer($registry);
		$query = <<<'SQL'
SELECT 'shop#single', "shop#double", `shop#identifier` FROM shop#products
SQL;

		$this->assertSame(
			<<<'SQL'
SELECT 'shop#single', "shop#double", `shop#identifier` FROM shop__products
SQL,
			$replacer->replaceQuery($query),
		);
	}

	public function testReplaceQuerySupportsEscapedAndDoubledQuotes(): void {
		$registry = new AliasRegistry();
		$registry->add('shop', 'shop__');
		$replacer = new AliasReplacer($registry);
		$query = <<<'SQL'
SELECT 'It\'s shop#one', 'It''s shop#two', "A\" shop#three", "A"" shop#four", `a\`shop#five`, `a``shop#six` FROM shop#products
SQL;

		$this->assertSame(
			<<<'SQL'
SELECT 'It\'s shop#one', 'It''s shop#two', "A\" shop#three", "A"" shop#four", `a\`shop#five`, `a``shop#six` FROM shop__products
SQL,
			$replacer->replaceQuery($query),
		);
	}

	public function testReplaceQueryReplacesAliasesInComments(): void {
		$registry = new AliasRegistry();
		$registry->add('shop', 'shop__');
		$replacer = new AliasReplacer($registry);
		$query = "SELECT 1 # shop#products\n/* shop#orders */";

		$this->assertSame(
			"SELECT 1 # shop__products\n/* shop__orders */",
			$replacer->replaceQuery($query),
		);
	}

	public function testReplaceQueryLeavesUnknownAliasesUnchanged(): void {
		$registry = new AliasRegistry();
		$registry->add('shop', 'shop__');
		$replacer = new AliasReplacer($registry);

		$this->assertSame(
			'SELECT * FROM unknown#products',
			$replacer->replaceQuery('SELECT * FROM unknown#products'),
		);
	}

	public function testReplaceQuerySkipsRegistryLookupWithoutCandidate(): void {
		$registry = new class extends AliasRegistry {
			public int $getAllCount = 0;

			public function getAll(): array {
				$this->getAllCount++;

				return parent::getAll();
			}
		};
		$replacer = new AliasReplacer($registry);

		$this->assertSame('SELECT 1', $replacer->replaceQuery('SELECT 1'));
		$this->assertSame('SELECT 1 # comment', $replacer->replaceQuery('SELECT 1 # comment'));
		$this->assertSame(0, $registry->getAllCount);
	}
}
