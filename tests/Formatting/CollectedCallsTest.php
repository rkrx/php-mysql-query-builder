<?php

namespace Kir\MySQL\Formatting;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

require_once __DIR__ . '/../../tools/bootstrap.php';

class CollectedCallsTest extends TestCase {
	public function testSingleAndGroupedCollectorRowsProduceTheSameMap(): void {
		$row = ['key' => '1:2', 'fingerprint' => 'fixture-hash', 'kind' => 'factory'];
		foreach([$row, [$row]] as $data) {
			$calls = new CollectedCalls();
			$calls->add(__FILE__, $data);
			self::assertSame(['1:2' => 'factory'], $calls->files()[__FILE__]['calls']);
		}
	}

	public function testConflictingScopesStayUnknown(): void {
		$calls = new CollectedCalls();
		$calls->add(__FILE__, ['key' => '1:2', 'fingerprint' => 'same', 'kind' => 'factory']);
		$calls->add(__FILE__, ['key' => '1:2', 'fingerprint' => 'same', 'kind' => '']);
		$calls->add(__FILE__, ['key' => '1:2', 'fingerprint' => 'same', 'kind' => 'factory']);
		self::assertSame(['1:2' => ''], $calls->files()[__FILE__]['calls']);
	}

	public function testUnsupportedOutputFailsInsteadOfSilentlySkipping(): void {
		$this->expectException(UnexpectedValueException::class);
		(new CollectedCalls())->add(__FILE__, ['unexpected' => 'shape']);
	}
}
