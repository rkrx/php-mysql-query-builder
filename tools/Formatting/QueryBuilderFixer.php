<?php

namespace Kir\MySQL\Formatting;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class QueryBuilderFixer implements FixerInterface {
	public function __construct(private TypeMap $types) {}

	public function isCandidate(Tokens $tokens): bool {
		return $tokens->isAnyTokenKindsFound([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_STRING]);
	}

	public function isRisky(): bool {
		return false;
	}

	public function fix(SplFileInfo $file, Tokens $tokens): void {
		$map = $this->types->forFile($file->getPathname());
		if($map === null || array_filter($map['calls']) === []) {
			return;
		}
		$code = $tokens->generateCode();
		$result = (new Layout())->format($code, $map);
		if($result !== $code) {
			$tokens->setCode($result);
		}
	}

	public function getDefinition(): FixerDefinitionInterface {
		return new FixerDefinition('Format this library\'s Query Builder calls using project-wide PHPStan types.', []);
	}

	public function getName(): string {
		return 'KirMysql/query_builder_layout';
	}

	public function getPriority(): int {
		return -100;
	}

	public function supports(SplFileInfo $file): bool {
		return true;
	}
}
