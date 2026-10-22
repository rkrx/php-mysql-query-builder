<?php

namespace Kir\MySQL\Formatting\PHPStan;

use Kir\MySQL\Formatting\CollectedCalls;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use UnexpectedValueException;

final class TypeMapFormatter implements ErrorFormatter {
	public function formatErrors(AnalysisResult $analysisResult, Output $output): int {
		$calls = new CollectedCalls();
		$fatal = $analysisResult->getNotFileSpecificErrors();
		foreach($analysisResult->getCollectedData() as $collected) {
			if($collected->getCollectorType() !== QueryCallCollector::class) {
				continue;
			}
			try {
				$calls->add(realpath($collected->getFilePath()) ?: $collected->getFilePath(), $collected->getData());
			} catch (UnexpectedValueException $error) {
				$fatal[] = $error->getMessage();
			}
		}
		foreach($analysisResult->getInternalErrorObjects() as $error) {
			$fatal[] = $error->getMessage();
		}
		foreach($analysisResult->getFileSpecificErrors() as $error) {
			if(!$error->canBeIgnored()) {
				$fatal[] = $error->getFile() . ':' . $error->getLine() . ': ' . $error->getMessage();
			}
		}
		$output->writeRaw(json_encode([
			'version' => 1,
			'files' => $calls->files(),
			'errors' => $fatal,
			'diagnosticCount' => $analysisResult->getTotalErrorsCount(),
		], JSON_THROW_ON_ERROR));
		return $fatal === [] ? 0 : 1;
	}
}
