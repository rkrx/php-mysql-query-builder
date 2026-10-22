<?php

// Tooling is loaded explicitly, never by the library's runtime autoloader.
spl_autoload_register(static function (string $class): void {
	$prefix = 'Kir\\MySQL\\Formatting\\';
	if(strncmp($class, $prefix, strlen($prefix)) !== 0) {
		return;
	}
	$file = __DIR__ . '/Formatting/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
	if(is_file($file)) {
		require_once $file;
	}
});
