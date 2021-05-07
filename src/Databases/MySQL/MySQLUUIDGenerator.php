<?php
namespace Kir\MySQL\Databases\MySQL;

use RuntimeException;
use Throwable;

class MySQLUUIDGenerator {
	/**
	 * @return string
	 */
	public static function genUUIDv4(): string {
		// Generate a unique id from a former random-uuid-generator
		try {
			return sprintf('ID%04x%04x%04x%04x%04x%04x%04x%04x',
				random_int(0, 0xffff),
				random_int(0, 0xffff),
				random_int(0, 0xffff),
				random_int(0, 0x0fff) | 0x4000,
				random_int(0, 0x3fff) | 0x8000,
				random_int(0, 0xffff),
				random_int(0, 0xffff),
				random_int(0, 0xffff)
			);
		} catch (Throwable $e) {
			// Should not throw an excepion under normal conditions
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
}
