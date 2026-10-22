<?php

require_once __DIR__ . '/vendor/autoload.php';

$configure = require __DIR__ . '/tools/php-cs-fixer.php';

return $configure(
	(new PhpCsFixer\Config())
		->setRules([])
		->setIndent("\t")
		->setLineEnding("\n")
		->setUsingCache(false)
		->setFinder(PhpCsFixer\Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests'])),
	__DIR__,
);
