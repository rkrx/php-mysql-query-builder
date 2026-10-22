<?php

require_once __DIR__ . '/bootstrap.php';

// The isolated shim exposes its classes only inside its PHAR.
if(!class_exists(PhpCsFixer\Config::class)) {
	$shim = dirname(__DIR__) . '/vendor/php-cs-fixer/shim/php-cs-fixer.phar';
	if(is_file($shim)) {
		require_once 'phar://' . $shim . '/vendor/autoload.php';
	}
}
