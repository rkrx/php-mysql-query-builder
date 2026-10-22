<?php

namespace Kir\MySQL\Formatting;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../tools/bootstrap.php';

class SourceIndexTest extends TestCase {
	public function testWhitespaceAndTrailingCommasKeepTheSameFingerprint(): void {
		$inline = new SourceIndex('<?php $query->fields([1, 2]);');
		$multiline = new SourceIndex("<?php\n\$query\n\t->fields([\n\t\t1,\n\t\t2,\n\t]);");
		self::assertSame($inline->fingerprint(), $multiline->fingerprint());
	}

	public function testLiteralAndCommentChangesInvalidateTheMap(): void {
		$source = '<?php $query->where("status = ?", "OPEN"); // guard';
		$before = new SourceIndex($source);
		self::assertNotSame($before->fingerprint(), (new SourceIndex(str_replace('OPEN', 'CLOSED', $source)))->fingerprint());
		self::assertNotSame($before->fingerprint(), (new SourceIndex(str_replace('guard', 'changed', $source)))->fingerprint());
	}
}
