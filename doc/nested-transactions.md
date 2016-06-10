# Nested transactions

```php
$mysql = new \Kir\MySQL\Databases\MySQL($pdo);

$mysql->delete()->from('test')->run();

$test = function () use ($mysql) {
	$name = $mysql->select()
	->field('t.name')
	->from('t', 'test')
	->where('t.id=?', 1)
	->fetchValue();
	printf("Current name is %s\n", $name);
};

$setName = function ($name) use ($mysql) {
	$mysql->insert()
	->into('test')
	->add('id', 1)
	->addOrUpdate('name', $name)
	->run();
};

$setName('Peter');
$test();

$mysql->transaction(function () use ($mysql, $setName, $test) {
	$setName('Paul');
	$test();

	// $mysql->transaction or...
	$mysql->dryRun(function () use ($mysql, $setName, $test) {
		$setName('Bert');
		$test();
	});
});

$test();
```

```
Current name is Peter
Current name is Paul
Current name is Bert
Current name is Paul
```

[Back](../README.md)
