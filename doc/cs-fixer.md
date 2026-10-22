# Query Builder CS-Fixer configuration

The [formatting rules](formatting.md) can be included in this project and in applications using the library. The entry point is [`tools/php-cs-fixer.php`](../tools/php-cs-fixer.php). It registers `KirMysql/query_builder_layout` on an existing CS-Fixer configuration and keeps its other rules and Finder.

The formatter is development tooling. The library's production autoloader does not load it, and the library has no additional runtime dependency.

## Using it in this repository

```sh
composer install --no-scripts
composer test:format
composer format:check
```

`format:check` is a dry run. Existing source files may have formatting differences and cause exit code 8. No project-wide source reformatting is part of adding this configuration. To apply the rules deliberately, run `composer format`, or restrict the command to selected files:

```sh
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff src/Databases/MySQL.php
```

## Including it in another project

Install these tools as development dependencies in the consuming application; dependency packages' development requirements are not installed transitively:

```sh
composer require --dev 'php-cs-fixer/shim:^3.95' 'phpstan/phpstan:^1.12 || ^2.0' 'nikic/php-parser:^5.0' --no-scripts
```

Use the already installed `friendsofphp/php-cs-fixer` instead of the shim if available; the include uses the same interfaces. PHPStan 1.12 and PHP-Parser 5 are the minimum supported tool APIs. Select versions that support the application's PHP syntax and runtime.

The integration was verified with PHPStan 1.12.32 in this repository and PHPStan 2.3.1 in a separate consuming project, using CS-Fixer 3.95.27, PHP-Parser 5.9.0, and PHP 8.5.2.

Create a dedicated configuration, or include the rule in a compatible existing one:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$configure = require __DIR__ . '/vendor/rkr/php-mysql-query-builder/tools/php-cs-fixer.php';

return $configure(
	(new PhpCsFixer\Config())
		->setRules([])
		->setIndent("\t")
		->setLineEnding("\n")
		->setFinder(PhpCsFixer\Finder::create()->in(__DIR__ . '/src')),
	__DIR__,
	__DIR__ . '/phpstan.neon',
);
```

The second argument is the application root containing `vendor/`. The optional third argument is the absolute PHPStan configuration path. If omitted, `phpstan.neon` or `phpstan.neon.dist` is used from that root. The application's normal PHPStan paths, exclusions, autoloading, stubs, extensions, and bootstrap files apply. Files outside the analysed paths, including excluded files, are not formatted by this rule. Widen the PHPStan configuration when wider coverage is required.

For tooling-only static analysis in this repository:

```sh
vendor/bin/phpstan analyse -c phpstan.neon --autoload-file=tools/fixer-bootstrap.php tools tests/Formatting
```

## Type recognition and failure behaviour

One PHPStan analysis supplies the call types for the entire run. The formatter recognises this library's SELECT interfaces, statement classes, prepared SELECT interfaces, prepared DDL statements, and database statement interface, including their implementations and subclasses. It handles typed properties and parameters, local assignments, PHPDoc types, inherited methods, and factories in other files through PHPStan's own inference.

| PHP form | Behaviour |
| --- | --- |
| `$db->select()->…`, including a property or getter receiver | Keep the complete receiver and factory name together; put subsequent calls on their own lines. |
| A typed helper, function, static factory, or constructor returning a query | Treat it as the starting expression; format the following query calls. |
| `$query->where()->orderBy()` | Put the existing query variable on its own starting line. |
| A single call on an existing query or prepared statement | Keep the call on one line when its arguments fit. |
| `prepare()->bindValue()->fetchRows()` or `prepare()->run()` | Include execution and fetch calls in the chain. |
| Inline subquery, CTE, UNION, callback, or nested array | Format each contained query and its arguments at their own indentation. |
| Query in a condition or boolean expression | Preserve operands, grouping, and evaluation order; format the query and condition across lines. |
| Nullsafe calls, named arguments, argument unpacking, or first-class callable syntax | Preserve operators, names, order, and placeholders. |
| `mixed`, unknown return types, dynamic calls without a known return type, or unions with a foreign builder | Do not guess that the uncertain call belongs to this library. Positively identified nested queries may still be formatted. |
| A foreign fluent API with identical method names | Leave it untouched. |
| `void` or `never` return types | Do not treat them as query factories. |

PHPStan uses its normal result cache, in a separate temporary cache directory for this integration. Type maps are kept in memory for the current run and are checked against the current PHP tokens, including literal contents and comments. Changes to whitespace and trailing commas remain compatible. Other token changes cause an error instead of applying a stale type map. Analysis infrastructure failures, internal errors, and non-ignorable analysis errors abort the run. Ordinary PHPStan findings do not abort formatting; only calls whose types are positively known are accepted.

The integration selects one CS-Fixer worker to avoid repeating the project-wide analysis in every fixer worker. PHPStan can still use its own parallel workers. This adds a project analysis to a formatting run; it is intentionally more expensive than name-based matching.

## Compatibility with other rules

`method_argument_space` must use `on_multiline => 'ignore'` if enabled in the same configuration. Its default `ensure_fully_multiline` mode conflicts with the agreed alias/table/expression grouping. The include rejects an incompatible setting, including one inherited through a rule set.

Rules that change PHP tokens, for example quote conversion or import rewriting, should run separately before this formatter. Whitespace rules and trailing commas can share a run. The provided repository configuration enables only the Query Builder rule; it does not introduce a global PHP style preset.

## Delivery milestones

These are planning estimates for a developer, not measured execution times.

| Milestone | Estimate | Acceptance |
| --- | --- | --- |
| M1: includable registration and type recognition, providing the first usable MVP | 2–3 hours | Typed factories, inheritance, and existing query variables are recognised; foreign and uncertain calls are left alone. |
| M2: chain and argument formatting | 2–3 hours | The 120-character boundary, grouped arguments, multiline arrays, nested queries, and comments have explicit examples. |
| M3: integration, regression tests, and documentation | 2–3 hours | Repeated formatting is stable; PHP syntax trees and comments are preserved; focused tests and static analysis pass. |
