<?php

namespace Kir\MySQL\Formatting;

/** Positions that survive whitespace changes and inserted trailing commas. */
final class SourceIndex {
	/** @var list<array{int, int}> */
	private array $ranges = [];
	private string $fingerprint;

	public function __construct(string $source) {
		$tokens = token_get_all($source);
		$significant = [];
		$comments = [];
		$offset = 0;
		foreach($tokens as $token) {
			$text = is_array($token) ? $token[1] : $token;
			if(is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
				$comments[] = $text;
			}
			if(!is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				$significant[] = [$offset, $offset + strlen($text) - 1, $text, is_array($token) && $token[0] === T_OPEN_TAG];
			}
			$offset += strlen($text);
		}
		$content = [];
		foreach($significant as $i => [$start, $end, $text, $openingTag]) {
			if($text === ',' && in_array($significant[$i + 1][2] ?? '', [')', ']'], true)) {
				continue;
			}
			$this->ranges[] = [$start, $end];
			$content[] = $openingTag ? rtrim($text) : $text;
		}
		$this->fingerprint = hash('sha256', serialize([$content, $comments]));
	}

	public function fingerprint(): string {
		return $this->fingerprint;
	}

	public function key(int $start, int $end): string {
		$first = $this->bound($start, 0, false);
		$last = $this->bound($end, 1, true) - 1;
		return $first <= $last ? $first . ':' . $last : ':';
	}

	private function bound(int $position, int $column, bool $inclusive): int {
		$low = 0;
		$high = count($this->ranges);
		while($low < $high) {
			$middle = intdiv($low + $high, 2);
			$value = $this->ranges[$middle][$column];
			if($value < $position || ($inclusive && $value === $position)) {
				$low = $middle + 1;
			} else {
				$high = $middle;
			}
		}
		return $low;
	}
}
