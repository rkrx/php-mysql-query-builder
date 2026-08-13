# Initialization

```PHP
$pdo = new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8', 'root', '', [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => true
]);
```

* `...charset=utf8...` you can use whatever charset you need. The query builder will not make any further modifications to the data. Data is always passed through 1:1.
* `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`: Will throw exceptions instead of failing silently.
* `PDO::ATTR_EMULATE_PREPARES => true`: There is an old PHP-PDO-bug. If you use a named parameter more than once using prepared statements and `PDO::ATTR_EMULATE_PREPARES => false`, only the first value will be used.

```PHP
$mysql = new MySQL($pdo);
$mysql->getAliasRegistry()->add('t', 'testdb.test__');
```

Registered aliases are also expanded in raw SQL passed to `query()`, `prepare()`,
and `exec()`:

```PHP
$mysql->getAliasRegistry()->add('shop', '`dv-shop`.shop__');
$mysql->query('SELECT * FROM shop#products');
// SELECT * FROM `dv-shop`.shop__products
```

Alias expressions inside single-quoted strings, double-quoted strings, and
backtick-quoted identifiers are left unchanged. SQL comments are not parsed
separately, so aliases in comments are expanded as well. Consequently, quote
characters inside comments can affect how the raw SQL is split into quoted and
unquoted parts.

You can also add options to specify global behavior

```PHP
// All keys are optional
$options = [
	'select-factory' => function ($db) {
		return new MyRunnableSelect($db, ...);
	},
	'select-options' => [
		'preserve-types-default' => true, // Always preserve types if not defined otherwise per query
		'fetch-object-class-default' => 'MyClass', // Standard is stdClass
	],
	'insert-factory' => function ($db) {
		return new MyRunnableInsert($db, ...);
	},
	'insert-options' => [], // Reserved for future usage
	'update-factory' => function ($db) {
		return new MyRunnableUpdate($db, ...);
	},
	'update-options' => [], // Reserved for future usage
	'delete-factory' => function ($db) {
		return new MyRunnableDelete($db, ...);
	},
	'delete-options' => [], // Reserved for future usage
];
$mysql = new MySQL($pdo, $options);
$mysql->getAliasRegistry()->add('t', 'testdb.test__');
```

[Back](../README.md)
