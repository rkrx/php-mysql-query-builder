<?php

namespace Kir\MySQL\Formatting;

use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPUnit\Framework\TestCase;
use PhpCsFixer\Config;
use PhpCsFixer\Tokenizer\Tokens;
use RuntimeException;
use SplFileInfo;

require_once __DIR__ . '/../../tools/fixer-bootstrap.php';

/** @phpstan-import-type FileTypeMap from TypeMap */
class QueryBuilderFormattingTest extends TestCase {
	/** @var FileTypeMap */
	private static array $map;
	private static string $input;

	public static function setUpBeforeClass(): void {
		$file = __DIR__ . '/fixtures/input.php.inc';
		self::$input = (string) file_get_contents($file);
		$types = new TypeMap(dirname(__DIR__, 2), __DIR__ . '/phpstan.neon');
		self::$map = $types->forFile($file) ?? throw new RuntimeException('Fixture was not analysed.');
	}

	public function testExpectedLayout(): void {
		$actual = (new Layout())->format(self::$input, self::$map);
		self::assertSame(file_get_contents(__DIR__ . '/fixtures/expected.php.inc'), $actual);
	}

	public function testFormattingPreservesTheSyntaxTreeAndComments(): void {
		$output = (new Layout())->format(self::$input, self::$map);
		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$printer = new Standard();
		$traverser = new NodeTraverser(new class extends NodeVisitorAbstract {
			public function enterNode(Node $node): ?Node {
				$node->setAttribute('comments', []);
				return null;
			}
		});
		self::assertSame($printer->prettyPrint($traverser->traverse($parser->parse(self::$input))), $printer->prettyPrint($traverser->traverse($parser->parse($output))));
		$comments = static fn(string $code): array => array_values(array_filter(token_get_all($code), static fn($token): bool => is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)));
		self::assertSame(array_column($comments(self::$input), 1), array_column($comments($output), 1));
	}

	public function testRepeatedFormattingIsStable(): void {
		$layout = new Layout();
		$first = $layout->format(self::$input, self::$map);
		self::assertSame($first, $layout->format($first, self::$map));
	}

	public function testTypeMapCannotBeReusedAfterCodeChanges(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('type map does not match');
		(new Layout())->format(str_replace("'categories'", "'changed_categories'", self::$input), self::$map);
	}

	public function testArrayWidthBoundaryIncludesTabsAndSemicolon(): void {
		$output = (new Layout())->format(self::$input, self::$map);
		preg_match('/function arrayWidth120.*?(?=\n})/s', $output, $atLimit);
		preg_match('/function arrayWidth121.*?(?=\n})/s', $output, $overLimit);
		self::assertStringNotContainsString("->fields([\n", $atLimit[0] ?? '');
		self::assertStringContainsString("->fields([\n", $overLimit[0] ?? '');
		preg_match('/^.*->fields\(.*$/m', $atLimit[0] ?? '', $line);
		self::assertSame(120, strlen(str_replace("\t", '    ', $line[0] ?? '')));
		preg_match('/function factoryWidth120.*?(?=\n})/s', $output, $factoryAtLimit);
		preg_match('/function factoryWidth121.*?(?=\n})/s', $output, $factoryOverLimit);
		self::assertStringNotContainsString("->select([\n", $factoryAtLimit[0] ?? '');
		self::assertStringContainsString("->select([\n", $factoryOverLimit[0] ?? '');
	}

	public function testIncludeRegistersTheFixerAndPreservesOtherRules(): void {
		$configure = require __DIR__ . '/../../tools/php-cs-fixer.php';
		$config = $configure((new Config())->setRules(['single_quote' => true]), dirname(__DIR__, 2), __DIR__ . '/phpstan.neon');
		self::assertTrue($config->getRules()['single_quote']);
		self::assertTrue($config->getRules()['KirMysql/query_builder_layout']);
		$tokens = Tokens::fromCode(self::$input);
		foreach($config->getCustomFixers() as $fixer) {
			$fixer->fix(new SplFileInfo(__DIR__ . '/fixtures/input.php.inc'), $tokens);
		}
		self::assertSame(file_get_contents(__DIR__ . '/fixtures/expected.php.inc'), $tokens->generateCode());
	}

	public function testConflictingArgumentRuleIsRejected(): void {
		$configure = require __DIR__ . '/../../tools/php-cs-fixer.php';
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('on_multiline=ignore');
		$configure((new Config())->setRules(['method_argument_space' => true]), dirname(__DIR__, 2));
	}
}
