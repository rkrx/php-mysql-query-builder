# Initialization

```PHP
$pdo = new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
```

```PHP
$mysql = new MySQL($pdo);
$mysql->getAliasRegistry()->add('t', 'testdb.test__');
```

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
