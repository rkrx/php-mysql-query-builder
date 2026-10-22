<?php

namespace Kir\MySQL\Formatting;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\ParserFactory;
use RuntimeException;

/**
 * Formats only expressions positively identified by PHPStan.
 * @phpstan-import-type FileTypeMap from TypeMap
 */
final class Layout {
	private string $source;
	private SourceIndex $index;
	/** @var array<string, string> */
	private array $calls;

	/** @param FileTypeMap $typeMap */
	public function format(string $source, array $typeMap): string {
		for($pass = 0; $pass < 4; ++$pass) {
			$this->source = $source;
			$this->index = new SourceIndex($source);
			if($this->index->fingerprint() !== $typeMap['fingerprint']) {
				throw new RuntimeException('The PHPStan type map does not match the PHP tokens. Run other code-transforming fixers separately before Query Builder formatting.');
			}
			$this->calls = $typeMap['calls'];
			$nodes = (new ParserFactory())->createForNewestSupportedVersion()->parse($source) ?? [];
			$result = $this->replaceChildren(0, strlen($source) - 1, $nodes);
			$result = $this->queryTabs($result);
			if($result === $source) {
				return $result;
			}
			$source = $result;
		}
		throw new RuntimeException('Query Builder formatting did not converge. The source was not written.');
	}

	private function kind(Node $node): string {
		if(!$node instanceof Expr\MethodCall && !$node instanceof Expr\NullsafeMethodCall
			&& !$node instanceof Expr\StaticCall && !$node instanceof Expr\FuncCall && !$node instanceof Expr\New_) {
			return '';
		}
		return $this->calls[$this->index->key($node->getStartFilePos(), $node->getEndFilePos())] ?? '';
	}

	private function hasQuery(Node $node): bool {
		if($this->kind($node) !== '') {
			return true;
		}
		foreach($this->children($node) as $child) {
			if($this->hasQuery($child)) {
				return true;
			}
		}
		return false;
	}

	private function render(Node $node, ?string $indent = null, ?string $prefix = null, bool $argument = false): string {
		$indent ??= $this->indent($node->getStartFilePos());
		$prefix ??= $this->linePrefix($node->getStartFilePos());
		if($node instanceof Expr\CallLike && $this->kind($node) !== '') {
			return $this->chain($node, $indent, $prefix);
		}
		if($argument && $node instanceof Expr\Array_) {
			return $this->array($node, $indent, $prefix);
		}
		if(($node instanceof Node\Stmt\If_ || $node instanceof Node\Stmt\ElseIf_
			|| $node instanceof Node\Stmt\While_ || $node instanceof Node\Stmt\Do_)
			&& $this->hasQuery($node->cond)) {
			return $this->condition($node, $indent);
		}
		if($this->isBoolean($node) && $this->hasQuery($node)) {
			return $this->boolean($node, $indent, $prefix, false);
		}
		$text = $this->replaceChildren($node->getStartFilePos(), $node->getEndFilePos(), $this->children($node), $argument);
		if(($node instanceof Node\Stmt\Expression || $node instanceof Node\Stmt\Return_)
			&& $this->hasQuery($node)) {
			$text = preg_replace('/\s+;$/', ';', $text) ?? $text;
		}
		return $this->shift($text, $this->indent($node->getStartFilePos()), $indent);
	}

	private function chain(Expr\CallLike $node, string $indent, string $prefix): string {
		$steps = [];
		$cursor = $node;
		while(($cursor instanceof Expr\MethodCall || $cursor instanceof Expr\NullsafeMethodCall)
			&& $this->kind($cursor) === 'call') {
			array_unshift($steps, $cursor);
			$operator = $this->operator($cursor);
			$gap = substr($this->source, $cursor->var->getEndFilePos() + 1, $operator - $cursor->var->getEndFilePos() - 1);
			$cursor = $cursor->var;
			if(trim($gap) !== '') {
				break;
			}
		}
		$factory = $cursor instanceof Expr\CallLike && $this->kind($cursor) === 'factory';
		if(!$factory && count($steps) === 1 && !$this->hasQuery($cursor)) {
			return $this->call($node, $indent, $prefix);
		}
		if($factory) {
			$wrapper = substr($this->source, $node->getStartFilePos(), $cursor->getStartFilePos() - $node->getStartFilePos());
			$text = $wrapper . $this->call($cursor, $indent, $prefix . $wrapper);
			$lastEnd = $cursor->getEndFilePos();
			if($steps !== []) {
				$operator = $this->operator($steps[0]);
				$text .= rtrim(substr($this->source, $lastEnd + 1, $operator - $lastEnd - 1));
				$lastEnd = $operator - 1;
			}
		} elseif($steps !== []) {
			$start = $node->getStartFilePos();
			$end = $this->operator($steps[0]) - 1;
			$text = $this->replaceChildren($start, $end, [$cursor]);
			$text = rtrim($text);
			$lastEnd = $end;
		} else {
			return $this->call($node, $indent, $prefix);
		}
		foreach($steps as $step) {
			$operator = $this->operator($step);
			$gap = substr($this->source, $lastEnd + 1, max(0, $operator - $lastEnd - 1));
			$text .= $this->commentGap($gap, $indent . "\t");
			$text .= $this->call($step, $indent . "\t", $indent . "\t", $operator);
			$lastEnd = $step->getEndFilePos();
		}
		return $text;
	}

	private function call(Expr\CallLike $node, string $indent, string $prefix, ?int $start = null): string {
		$parts = $this->callParts($node);
		if($parts === null) {
			return $this->slice($node);
		}
		[$open, $close] = $parts;
		$start ??= $node->getStartFilePos();
		$head = substr($this->source, $start, $open - $start + 1);
		if(($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall)
			&& $this->kind($node) === 'factory') {
			$bridgeStart = $node->var->getEndFilePos() + 1;
			$bridgeEnd = $node->name->getStartFilePos() - 1;
			if(!$this->hasComments($bridgeStart, $bridgeEnd)) {
				$bridge = preg_replace('/\s+/', '', substr($this->source, $bridgeStart, $bridgeEnd - $bridgeStart + 1));
				$head = substr($this->source, $start, $bridgeStart - $start) . $bridge
					. substr($this->source, $bridgeEnd + 1, $open - $bridgeEnd);
			}
		}
		$args = $node->getRawArgs();
		foreach($args as $arg) {
			if(!$arg instanceof Arg) {
				return substr($this->source, $start, $node->getEndFilePos() - $start + 1);
			}
		}
		/** @var list<Arg> $args */
		$args = array_values($args);
		if($args === []) {
			$inside = substr($this->source, $open + 1, $close - $open - 1);
			return $head . $inside . ')';
		}
		$inline = [];
		foreach($args as $arg) {
			$inline[] = $this->arg($arg, $indent, $prefix . $head, false);
		}
		$candidate = $head . implode(', ', $inline) . ')';
		$hasTrivia = $this->hasComments($open + 1, $args[0]->getStartFilePos() - 1);
		foreach($args as $i => $arg) {
			$nextStart = isset($args[$i + 1]) ? $args[$i + 1]->getStartFilePos() : $close;
			$hasTrivia = $hasTrivia || $this->hasComments($arg->getEndFilePos() + 1, $nextStart - 1);
		}
		if(!$hasTrivia && !str_contains($candidate, "\n") && $this->width($prefix . $candidate . $this->suffix($node)) <= 120) {
			return $candidate;
		}
		if(!$hasTrivia && count($args) === 1 && $args[0]->value instanceof Expr\Array_) {
			return $head . $this->arg($args[0], $indent, $prefix . $head, true, true) . ')';
		}
		$name = isset($node->name) && $node->name instanceof Node\Identifier ? strtolower($node->name->toString()) : '';
		$joins = ['joinleft', 'joinright', 'joininner'];
		$expressions = ['where', 'having', 'addexpr', 'updateexpr', 'addorupdateexpr', 'setexpr', 'field', 'orderby'];
		$group = in_array($name, $joins, true) ? min(3, count($args)) : (in_array($name, $expressions, true) ? 1 : 0);
		$named = count(array_filter($args, static fn(Arg $arg): bool => $arg->name !== null)) > 0;
		if($hasTrivia || count(array_filter($inline, static fn(string $value): bool => str_contains($value, "\n"))) > 0
			|| $named) {
			$group = 0;
		}
		while($group > 0 && $this->width($prefix . $head . implode(', ', array_slice($inline, 0, $group)) . ',') > 120) {
			$group = $group === 3 ? 2 : 0;
		}
		$text = $head;
		if($group > 0) {
			$text .= implode(', ', array_slice($inline, 0, $group)) . ',';
		}
		$previousEnd = $group === 0 ? $open : $args[$group - 1]->getEndFilePos();
		for($i = $group; $i < count($args); ++$i) {
			$arg = $args[$i];
			$gap = substr($this->source, $previousEnd + 1, max(0, (int) ($arg->getStartFilePos() - $previousEnd - 1)));
			$text .= $this->commentGap($gap, $indent . "\t");
			if($i === 0 && !$hasTrivia && !$named && count($args) >= 2 && in_array($name, [...$joins, 'from', 'table'], true)
				&& !str_contains($inline[0] . $inline[1], "\n")
				&& $this->width($indent . "\t" . $inline[0] . ', ' . $inline[1] . ',') <= 120) {
				$text .= $inline[0] . ', ' . $inline[1] . ',';
				$previousEnd = $args[++$i]->getEndFilePos();
				continue;
			}
			$text .= $this->arg($arg, $indent . "\t", $indent . "\t", true) . ',';
			$previousEnd = $arg->getEndFilePos();
		}
		$gap = substr($this->source, $previousEnd + 1, max(0, (int) ($close - $previousEnd - 1)));
		$text .= $this->commentGap($gap, $indent);
		return $text . ')';
	}

	private function arg(Arg $arg, string $indent, string $prefix, bool $wrap, bool $forceArray = false): string {
		$head = substr($this->source, $arg->getStartFilePos(), $arg->value->getStartFilePos() - $arg->getStartFilePos());
		if($arg->value instanceof Expr\Array_) {
			return $head . $this->array($arg->value, $indent, $prefix . $head, $wrap, $forceArray);
		}
		return $head . $this->render($arg->value, $indent, $prefix . $head, true);
	}

	private function array(Expr\Array_ $array, string $indent, string $prefix, bool $wrap = true, bool $force = false): string {
		$long = $array->getAttribute('kind') === Expr\Array_::KIND_LONG;
		$open = $long ? strpos($this->source, '(', $array->getStartFilePos()) : $array->getStartFilePos();
		$head = substr($this->source, $array->getStartFilePos(), $open - $array->getStartFilePos() + 1);
		$close = $long ? ')' : ']';
		$entries = [];
		foreach($array->items as $item) {
			$entries[] = $this->arrayItem($item, $indent, $prefix . $head, false);
		}
		$candidate = $head . implode(', ', $entries) . $close;
		$original = $this->slice($array);
		if(!$force && !str_contains($original, "\n") && !$this->hasComments($array->getStartFilePos(), $array->getEndFilePos())
			&& !str_contains($candidate, "\n") && (!$wrap || $this->width($prefix . $candidate . ',') <= 120)) {
			return $candidate;
		}
		if($array->items === [] && !$this->hasComments($open + 1, $array->getEndFilePos() - 1)) {
			return $head . $close;
		}
		$text = $head;
		$previousEnd = $open;
		foreach($array->items as $item) {
			$gap = substr($this->source, $previousEnd + 1, max(0, $item->getStartFilePos() - $previousEnd - 1));
			$text .= $this->commentGap($gap, $indent . "\t");
			$text .= $this->arrayItem($item, $indent . "\t", $indent . "\t", true) . ',';
			$previousEnd = $item->getEndFilePos();
		}
		$gap = substr($this->source, $previousEnd + 1, max(0, $array->getEndFilePos() - $previousEnd - 1));
		return $text . $this->commentGap($gap, $indent) . $close;
	}

	private function arrayItem(Node\ArrayItem $item, string $indent, string $prefix, bool $wrap): string {
		$head = substr($this->source, $item->getStartFilePos(), $item->value->getStartFilePos() - $item->getStartFilePos());
		if($item->key !== null && !$this->hasComments($item->getStartFilePos(), $item->value->getStartFilePos() - 1)) {
			$head = $this->render($item->key, $indent, $prefix) . ' => ' . ($item->byRef ? '&' : '');
		}
		$value = $item->value instanceof Expr\Array_
			? $this->array($item->value, $indent, $prefix . $head, $wrap)
			: $this->render($item->value, $indent, $prefix . $head, true);
		if($wrap && $item->key !== null && !str_contains($head . $value, "\n")
			&& $this->width($prefix . $head . $value . ',') > 120) {
			return rtrim($head) . "\n" . $indent . "\t" . $value;
		}
		return $head . $value;
	}

	/** Convert indentation only on query lines, never inside literal tokens. */
	private function queryTabs(string $source): string {
		if(!preg_match('/^[\t ]* {4}/m', $source)) {
			return $source;
		}
		$index = new SourceIndex($source);
		$nodes = (new ParserFactory())->createForNewestSupportedVersion()->parse($source) ?? [];
		$lines = [];
		$pending = $nodes;
		while($pending !== []) {
			$node = array_pop($pending);
			$key = $index->key($node->getStartFilePos(), $node->getEndFilePos());
			if($node instanceof Expr\CallLike && ($this->calls[$key] ?? '') !== '') {
				for($line = $node->getStartLine(); $line <= $node->getEndLine(); ++$line) {
					$lines[$line] = true;
				}
			}
			array_push($pending, ...$this->children($node));
		}
		$result = '';
		$offset = 0;
		$line = 1;
		foreach(token_get_all($source) as $token) {
			$text = is_array($token) ? $token[1] : $token;
			$newlines = substr_count($text, "\n");
			if(is_array($token) && $token[0] === T_WHITESPACE) {
				$parts = explode("\n", $text);
				foreach($parts as $i => $part) {
					if(isset($lines[$line + $i]) && preg_match('/^[\t ]*$/D', $part)
						&& ($i > 0 || $offset === 0 || $source[$offset - 1] === "\n")) {
						$width = strlen(str_replace("\t", '    ', $part));
						$parts[$i] = str_repeat("\t", intdiv($width, 4)) . str_repeat(' ', $width % 4);
					}
				}
				$result .= implode("\n", $parts);
			} else {
				$result .= $text;
			}
			$line += $newlines;
			$offset += strlen($text);
		}
		return $result;
	}

	private function condition(Node\Stmt\If_|Node\Stmt\ElseIf_|Node\Stmt\While_|Node\Stmt\Do_ $node, string $indent): string {
		$start = $node->getStartFilePos();
		$conditionStart = $node->cond->getStartFilePos();
		$searchStart = $start;
		if($node instanceof Node\Stmt\Do_) {
			$offset = $start - 6;
			foreach(token_get_all('<?php ' . substr($this->source, $start, $conditionStart - $start)) as $token) {
				$text = is_array($token) ? $token[1] : $token;
				if(is_array($token) && $token[0] === T_WHILE) {
					$searchStart = $offset + strlen($text);
				}
				$offset += strlen($text);
			}
		}
		$open = $this->punctuation($searchStart, $conditionStart, '(');
		if($open === null) {
			return $this->replaceChildren($start, $node->getEndFilePos(), $this->children($node));
		}
		$close = $this->matchingClose($open, $node->getEndFilePos());
		$conditionIndent = $indent . "\t";
		$condition = $this->isBoolean($node->cond)
			? $this->boolean($node->cond, $conditionIndent, $conditionIndent, true)
			: $this->render($node->cond, $conditionIndent, $conditionIndent);
		$before = substr($this->source, $open + 1, $conditionStart - $open - 1);
		$after = substr($this->source, $node->cond->getEndFilePos() + 1, $close - $node->cond->getEndFilePos() - 1);
		// Preserve grouping parentheses and comments around the condition expression.
		$condition = ltrim($before) . $condition . rtrim($after);
		$rest = $this->replaceChildren($close, $node->getEndFilePos(), array_filter($this->children($node), static fn(Node $child): bool => $child->getStartFilePos() > $close));
		$head = $this->replaceChildren($start, $open, array_filter($this->children($node), static fn(Node $child): bool => $child->getEndFilePos() < $open));
		return $head . "\n" . $conditionIndent . $condition . "\n" . $indent . $rest;
	}

	private function boolean(Expr\BinaryOp $node, string $indent, string $prefix, bool $condition): string {
		$continuation = $condition ? $indent : $indent . "\t";
		$left = $this->isBoolean($node->left)
			? $this->boolean($node->left, $indent, $prefix, $condition)
			: $this->render($node->left, $indent, $prefix);
		$between = substr($this->source, $node->left->getEndFilePos() + 1, $node->right->getStartFilePos() - $node->left->getEndFilePos() - 1);
		$operator = $node->getOperatorSigil();
		$head = substr($this->source, $node->getStartFilePos(), $node->left->getStartFilePos() - $node->getStartFilePos());
		$tail = substr($this->source, $node->right->getEndFilePos() + 1, $node->getEndFilePos() - $node->right->getEndFilePos());
		if($this->hasComments($node->left->getEndFilePos() + 1, $node->right->getStartFilePos() - 1)) {
			// Keep comment/operator order exactly as written.
			return $head . $left . $between . $this->render($node->right, $continuation, $continuation) . $tail;
		}
		$position = strpos($between, $operator);
		if($position === false) {
			throw new RuntimeException('Cannot locate the boolean operator.');
		}
		$leftSuffix = trim(substr($between, 0, $position));
		$rightPrefix = trim(substr($between, $position + strlen($operator)));
		$right = $this->render($node->right, $continuation, $continuation . $operator . ' ' . $rightPrefix);
		return $head . $left . $leftSuffix . "\n" . $continuation . $operator . ' ' . $rightPrefix . $right . $tail;
	}

	/** @phpstan-assert-if-true Expr\BinaryOp $node */
	private function isBoolean(Node $node): bool {
		return $node instanceof Expr\BinaryOp\BooleanAnd || $node instanceof Expr\BinaryOp\BooleanOr
			|| $node instanceof Expr\BinaryOp\LogicalAnd || $node instanceof Expr\BinaryOp\LogicalOr;
	}

	/** @param array<Node> $children */
	private function replaceChildren(int $start, int $end, array $children, bool $argument = false): string {
		usort($children, static fn(Node $a, Node $b): int => $a->getStartFilePos() <=> $b->getStartFilePos());
		$text = '';
		$offset = $start;
		foreach($children as $child) {
			if($child->getStartFilePos() < $offset || $child->getEndFilePos() > $end) {
				continue;
			}
			$text .= substr($this->source, $offset, $child->getStartFilePos() - $offset);
			$text .= $this->render($child, null, null, $argument && !$child instanceof Expr\Closure && !$child instanceof Expr\ArrowFunction);
			$offset = $child->getEndFilePos() + 1;
		}
		return $text . substr($this->source, $offset, max(0, (int) ($end - $offset + 1)));
	}

	/** @return list<Node> */
	private function children(Node $node): array {
		$children = [];
		foreach($node->getSubNodeNames() as $name) {
			foreach(is_array($node->$name) ? $node->$name : [$node->$name] as $child) {
				if($child instanceof Node && $child->getStartFilePos() >= 0) {
					$children[] = $child;
				}
			}
		}
		return $children;
	}

	private function operator(Expr\MethodCall|Expr\NullsafeMethodCall $node): int {
		$start = $node->var->getEndFilePos() + 1;
		$text = substr($this->source, $start, $node->name->getStartFilePos() - $start);
		$offset = $start - 6;
		foreach(token_get_all('<?php ' . $text) as $token) {
			if(is_array($token) && in_array($token[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
				return $offset;
			}
			$offset += strlen(is_array($token) ? $token[1] : $token);
		}
		throw new RuntimeException('Cannot locate the method call operator.');
	}

	/** @return array{int, int}|null */
	private function callParts(Expr\CallLike $node): ?array {
		$name = $node instanceof Expr\New_ ? $node->class : ($node->name ?? null);
		if(!$name instanceof Node) {
			return null;
		}
		$open = $this->punctuation($name->getEndFilePos() + 1, $node->getEndFilePos(), '(');
		return $open === null ? null : [$open, $this->matchingClose($open, $node->getEndFilePos())];
	}

	private function punctuation(int $start, int $end, string $character): ?int {
		if($end < $start) {
			return null;
		}
		$offset = $start - 6;
		foreach(token_get_all('<?php ' . substr($this->source, $start, $end - $start + 1)) as $token) {
			$text = is_array($token) ? $token[1] : $token;
			if($token === $character) {
				return $offset;
			}
			$offset += strlen($text);
		}
		return null;
	}

	private function matchingClose(int $open, int $end): int {
		$offset = $open - 6;
		$depth = 0;
		foreach(token_get_all('<?php ' . substr($this->source, $open, $end - $open + 1)) as $token) {
			$text = is_array($token) ? $token[1] : $token;
			if($token === '(') {
				++$depth;
			} elseif($token === ')' && --$depth === 0) {
				return $offset;
			}
			$offset += strlen($text);
		}
		throw new RuntimeException('Cannot locate the closing parenthesis.');
	}

	private function hasComments(int $start, int $end): bool {
		if($end < $start) {
			return false;
		}
		foreach(token_get_all('<?php ' . substr($this->source, $start, $end - $start + 1)) as $token) {
			if(is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
				return true;
			}
		}
		return false;
	}

	private function commentGap(string $gap, string $indent): string {
		$text = '';
		foreach(token_get_all('<?php ' . $gap) as $token) {
			if(is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
				$text .= ($text === '' ? ' ' : '') . $token[1] . "\n" . $indent;
			}
		}
		return $text === '' ? "\n" . $indent : rtrim($text, "\t ") . $indent;
	}

	private function suffix(Node $node): string {
		$tail = substr($this->source, $node->getEndFilePos() + 1);
		return preg_match('/^[\t ]*([;,])/', $tail, $match) ? $match[1] : '';
	}

	private function slice(Node $node): string {
		return substr($this->source, $node->getStartFilePos(), $node->getEndFilePos() - $node->getStartFilePos() + 1);
	}

	private function linePrefix(int $position): string {
		$before = substr($this->source, 0, $position);
		$line = strrpos($before, "\n");
		return substr($before, $line === false ? 0 : $line + 1);
	}

	private function indent(int $position): string {
		preg_match('/^[\t ]*/', $this->linePrefix($position), $match);
		return $match[0] ?? '';
	}

	private function width(string $text): int {
		$expanded = str_replace("\t", '    ', $text);
		$count = preg_match_all('/./us', $expanded);
		return $count === false ? strlen($expanded) : $count;
	}

	private function shift(string $text, string $old, string $new): string {
		if($old === $new) {
			return $text;
		}
		$result = '';
		foreach(token_get_all('<?php ' . $text) as $i => $token) {
			if($i === 0) {
				continue;
			}
			$content = is_array($token) ? $token[1] : $token;
			if(is_array($token) && $token[0] === T_WHITESPACE) {
				$content = preg_replace_callback('/\n([\t ]*)/', static function (array $match) use ($old, $new): string {
					return "\n" . (str_starts_with($match[1], $old) ? $new . substr($match[1], strlen($old)) : $match[1]);
				}, $content) ?? $content;
			}
			$result .= $content;
		}
		return $result;
	}
}
