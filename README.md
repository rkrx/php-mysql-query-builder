mysql query builder (php 5.4+)
==============================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a57881f2-af75-48b7-9f5f-e821cdb75d0c/mini.png)](https://insight.sensiolabs.com/projects/a57881f2-af75-48b7-9f5f-e821cdb75d0c)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rkrx/php-mysql-query-builder/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rkrx/php-mysql-query-builder/?branch=master)
[![Build Status](https://travis-ci.org/rkrx/php-mysql-query-builder.svg)](https://travis-ci.org/rkrx/php-mysql-query-builder)
[![Latest Stable Version](https://poser.pugx.org/rkr/php-mysql-query-builder/v/stable)](https://packagist.org/packages/rkr/php-mysql-query-builder)
[![License](https://poser.pugx.org/rkr/php-mysql-query-builder/license)](https://packagist.org/packages/rkr/php-mysql-query-builder)

Simple mysql query builder to build select, insert, update and delete queries with conditional parts.
This library was initially not intended to build prepared statements, but this is also possible.
The main motive for this library is an environment where a lot of things are automated.

Here a few things to keep in mind:

* The charset is up to you. No special binding to UTF8, although UTF8 is the default.
* The order of method-calls of each statement-builder is irrelevant. The resulting query will always render the right order.
* No animals were harmed due to the production of this library.

## Some examples

### Initialization

```PHP
$pdo = new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
```

```PHP
$mysql = new MySQL($pdo);
$mysql->getAliasRegistry()->add('t', 'testdb.test__');
```

### Select

```PHP
$subSelect = function ($id) use ($mysql) {
    return $mysql->select()
    ->field('t.id')
    ->from('t', 'table')
    ->where('t.foreign_id=?', $id);
};

$select = $mysql->select()
->field('COUNT(*)', 'customer_count')
->from('t1', 't#test1')
->joinInner('t2', 't#test2', 't2.test_id = t1.id AND t2.field1 = ?', 123)
->joinLeft('t3', 't#test3', 't3.test_id = t1.id')
->joinRight('t4', 't#test4', 't4.test_id = t1.id')
->joinRight('t5', $subSelect(10), 't5.test_id = t1.id')
->orderBy('t1.field1')
->orderBy('t1.field2', 'DESC')
->limit(100)
->offset(50);
```

```PHP
if($contition === true) {
	$select->where('t1.somefield = ?', $someValue);
}
```

```PHP
$rows = $select->fetchRows();
foreach($rows as $row) {
	print_r($row);
}
```

* The order of method-calls doesn't matter.

### Insert

You can insert key-value-arrays with `addAll`, `updateAll`, `addOrUpdateAll`. As the second parameter you can provide an array to specify the only fields to consider.
 
```PHP
$id = $mysql->insert()
->into('test')
->addOrUpdateAll($data, ['field1', 'field2', 'field3'])
->add('created_by', $userId)
->addOrUpdate('updated_by', $userId)
->addExpr('created_at=NOW()')
->addOrUpdateExpr('updated_at=NOW()')
->run();
```

* `insert()` alwasy returns an id, no matter if a dataset was actually inserted or updated.
* You can mass-insert by using `insert()->...->insertRows(array $rows)`.

There is also an option to build an `INSERT INTO ... SELECT ... FROM ... ON DUPLICATE KEY UPDATE ...`:

```PHP
$id = $mysql->insert()
->into('test')
->addExpr('field1=:field1')
->addOrUpdateExpr('field2=:field2')
->addExpr('field3=NOW()')
->from(
	$mysql->select()
	->field('a.myfield1', 'field1')
	->field('a.myfield2', 'field2')
	->from('a', 'mytable')
	->where('field=?', 1)
)->run();
```

### Update

```PHP
$mysql->update()
->table('t1', 'test1')
->joinLeft('t2', 'test2', 't1.id = t2.test_id')
->setAll($data)
->where("t1.field1 = ? OR t2.field2 > ?", 1, 10)
->where("field IN (?)", [1, 2, 3, 4, 5, 6])
->run();
```

### Delete

You can use joins in delete-statements. But only the rows of tables specified in `from` will be modified (deleted).

```PHP
$mysql->delete()
->from('t1', 'test1')
->joinLeft('t2', 'test2', 't1.id=t2.test_id')
->where('t1.field1=? AND t2.field2 > ?', 1, 10)
->run();
```

### True nested transactions

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