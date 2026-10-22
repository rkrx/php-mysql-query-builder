<?php

use Kir\MySQL\Formatting\QueryBuilderFixer;
use Kir\MySQL\Formatting\TypeMap;
use PhpCsFixer\ConfigInterface;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Runner\Parallel\ParallelConfig;

require_once __DIR__ . '/bootstrap.php';

/** Include from the consuming project's CS-Fixer configuration. */
return static function (ConfigInterface $config, string $projectRoot, ?string $phpstanConfiguration = null): ConfigInterface {
	$projectRoot = realpath($projectRoot) ?: $projectRoot;
	$autoload = $projectRoot . '/vendor/autoload.php';
	if(is_file($autoload)) {
		require_once $autoload;
	}
	if(!class_exists(PhpParser\ParserFactory::class) || !class_exists(PhpParser\Node\ArrayItem::class)) {
		throw new RuntimeException('Query Builder formatting requires nikic/php-parser:^5.0 as a development dependency.');
	}
	$phpstanConfiguration ??= is_file($projectRoot . '/phpstan.neon')
		? $projectRoot . '/phpstan.neon' : $projectRoot . '/phpstan.neon.dist';
	$rules = $config->getRules();
	$resolved = (new RuleSet($rules))->getRules();
	$arguments = $resolved['method_argument_space'] ?? false;
	if($arguments !== false && ($arguments === true || ($arguments['on_multiline'] ?? 'ensure_fully_multiline') !== 'ignore')) {
		throw new RuntimeException('method_argument_space must use on_multiline=ignore for grouped Query Builder arguments. Alternatively run the included Query Builder configuration separately.');
	}
	$rules['KirMysql/query_builder_layout'] = true;
	$config->registerCustomFixers([new QueryBuilderFixer(new TypeMap($projectRoot, $phpstanConfiguration))]);
	$config->setRules($rules);
	// One project-wide analysis per run, rather than one analysis per fixer worker.
	if(method_exists($config, 'setParallelConfig')) {
		$config->setParallelConfig(new ParallelConfig(1));
	}
	return $config;
};
