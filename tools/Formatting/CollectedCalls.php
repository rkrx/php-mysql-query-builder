<?php

namespace Kir\MySQL\Formatting;

use UnexpectedValueException;

/** @phpstan-import-type FileTypeMap from TypeMap */
final class CollectedCalls {
	/** @var array<string, FileTypeMap> */
	private array $files = [];

	/** @param mixed $data */
	public function add(string $file, $data): void {
		if(!is_array($data)) {
			throw new UnexpectedValueException('Unsupported PHPStan collector output.');
		}
		// PHPStan 1.x emits a row per call; 2.3+ groups rows by file/collector.
		$rows = isset($data['key']) ? [$data] : $data;
		foreach($rows as $row) {
			if(!is_array($row) || !is_string($row['key'] ?? null) || !is_string($row['fingerprint'] ?? null)
				|| !in_array($row['kind'] ?? null, ['', 'call', 'factory'], true)) {
				throw new UnexpectedValueException('Unsupported PHPStan collector output.');
			}
			if(isset($this->files[$file]['fingerprint']) && $this->files[$file]['fingerprint'] !== $row['fingerprint']) {
				throw new UnexpectedValueException('Inconsistent PHPStan source fingerprints.');
			}
			$this->files[$file]['fingerprint'] = $row['fingerprint'];
			$key = $row['key'];
			// A call visited in multiple scopes is accepted only if all agree.
			if(isset($this->files[$file]['calls'][$key]) && $this->files[$file]['calls'][$key] !== $row['kind']) {
				$this->files[$file]['calls'][$key] = '';
			} else {
				$this->files[$file]['calls'][$key] = $row['kind'];
			}
		}
	}

	/** @return array<string, FileTypeMap> */
	public function files(): array {
		return $this->files;
	}
}
