<?php

namespace Kir\MySQL\Formatting\PHPStan;

use Kir\MySQL\Builder\Internal\DDLRunnable;
use Kir\MySQL\Builder\PreparedSelect;
use Kir\MySQL\Builder\Select;
use Kir\MySQL\Builder\Statement;
use Kir\MySQL\Database\DatabaseStatement;
use Kir\MySQL\Formatting\SourceIndex;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Collector<Expr, array{key: string, fingerprint: string, kind: string}>
 */
final class QueryCallCollector implements Collector {
	/** @var array<string, SourceIndex> */
	private array $sources = [];

	public function getNodeType(): string {
		return Expr::class;
	}

	public function processNode(Node $node, Scope $scope): ?array {
		if(!$node instanceof Expr\MethodCall && !$node instanceof Expr\NullsafeMethodCall
			&& !$node instanceof Expr\StaticCall && !$node instanceof Expr\FuncCall && !$node instanceof Expr\New_) {
			return null;
		}
		$kind = '';
		if($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall) {
			$receiver = TypeCombinator::removeNull($scope->getType($node->var));
			if($this->isBuilder($receiver) && $node->name instanceof Node\Identifier
				&& $receiver->hasMethod($node->name->toString())->yes()
				&& $receiver->getMethod($node->name->toString(), $scope)->isPublic()) {
				$kind = 'call';
			}
		}
		if($kind === '' && $this->isBuilder(TypeCombinator::removeNull($scope->getType($node)))) {
			$kind = 'factory';
		}
		$file = $scope->getFile();
		$this->sources[$file] ??= new SourceIndex((string) file_get_contents($file));
		return [
			'key' => $this->sources[$file]->key($node->getStartFilePos(), $node->getEndFilePos()),
			'fingerprint' => $this->sources[$file]->fingerprint(),
			'kind' => $kind,
		];
	}

	private function isBuilder(Type $type): bool {
		// The bottom type `never` is a subtype of every type, but is not a builder.
		if($type->getObjectClassNames() === []) {
			return false;
		}
		$builders = TypeCombinator::union(
			new ObjectType(Select::class),
			new ObjectType(Statement::class),
			new ObjectType(PreparedSelect::class),
			new ObjectType(DDLRunnable::class),
			new ObjectType(DatabaseStatement::class),
		);
		return $builders->isSuperTypeOf($type)->yes();
	}
}
