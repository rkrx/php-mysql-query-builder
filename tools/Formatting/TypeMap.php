<?php

namespace Kir\MySQL\Formatting;

use RuntimeException;

/** @phpstan-type FileTypeMap array{fingerprint: string, calls: array<string, string>} */
final class TypeMap {
	/** @var array<string, FileTypeMap>|null */
	private ?array $files = null;

	public function __construct(
		private string $projectRoot,
		private string $configuration,
	) {}

	/** @return FileTypeMap|null */
	public function forFile(string $file): ?array {
		$this->files ??= $this->analyse();
		return $this->files[realpath($file) ?: $file] ?? null;
	}

	/** @return array<string, FileTypeMap> */
	private function analyse(): array {
		$phpstan = $this->projectRoot . '/vendor/phpstan/phpstan/phpstan';
		if(!is_file($phpstan) || !is_file($this->configuration)) {
			throw new RuntimeException('Query Builder formatting requires PHPStan >=1.12 and a project PHPStan configuration.');
		}
		$temporary = tempnam(sys_get_temp_dir(), 'query-builder-format-');
		if($temporary === false) {
			throw new RuntimeException('Cannot create the temporary PHPStan configuration.');
		}
		$config = $temporary . '.neon';
		rename($temporary, $config);
		$bootstrap = dirname(__DIR__) . '/bootstrap.php';
		$version = hash('sha256', file_get_contents(__FILE__) . file_get_contents(__DIR__ . '/SourceIndex.php')
			. file_get_contents(__DIR__ . '/CollectedCalls.php')
			. file_get_contents(__DIR__ . '/PHPStan/QueryCallCollector.php') . file_get_contents(__DIR__ . '/PHPStan/TypeMapFormatter.php'));
		$cache = sys_get_temp_dir() . '/kir-mysql-formatting/' . hash('sha256', $this->projectRoot . $this->configuration . $version);
		$quote = static fn(string $value): string => "'" . str_replace("'", "''", $value) . "'";
		file_put_contents($config, "includes:\n    - " . $quote($this->configuration) . "\n"
			. "parameters:\n    tmpDir: " . $quote($cache) . "\n"
			. "services:\n    -\n        class: Kir\\MySQL\\Formatting\\PHPStan\\QueryCallCollector\n"
			. "        tags: [phpstan.collector]\n"
			. "    errorFormatter.queryBuilderTypes:\n        class: Kir\\MySQL\\Formatting\\PHPStan\\TypeMapFormatter\n");
		$command = [PHP_BINARY, $phpstan, 'analyse', '--configuration=' . $config,
			'--autoload-file=' . $bootstrap, '--error-format=queryBuilderTypes', '--no-progress', '--no-ansi', '--memory-limit=1G'];
		try {
			$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', $temporary, 'w']], $pipes, $this->projectRoot);
			if(!is_resource($process)) {
				throw new RuntimeException('Cannot start PHPStan for Query Builder formatting.');
			}
			fclose($pipes[0]);
			$result = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			$exit = proc_close($process);
			$data = json_decode((string) $result, true);
			if($exit !== 0 || !is_array($data) || ($data['version'] ?? null) !== 1 || ($data['errors'] ?? []) !== []) {
				$details = is_array($data) ? implode("\n", $data['errors'] ?? []) : (string) $result;
				throw new RuntimeException("Query Builder type analysis failed:\n" . $details . "\n" . file_get_contents($temporary));
			}
			return $data['files'];
		} finally {
			unlink($config);
			if(is_file($temporary)) {
				unlink($temporary);
			}
		}
	}
}
