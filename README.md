mysql query builder (php 5.4+)
==============================

[![Build Status](https://travis-ci.org/rkrx/php-mysql-query-builder.svg)](https://travis-ci.org/rkrx/php-mysql-query-builder)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rkrx/php-mysql-query-builder/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rkrx/php-mysql-query-builder/?branch=master)

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
$pdo = new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mysql = new MySQL($pdo);
$mysql->getAliasRegistry()->add('textprefix', 'testdb.test__');
```

### Select

```PHP
$select = $mysql->select(['customer_count' => 'COUNT(*)'])
->from('t1', 'textprefix#test1') // You are forced to used aliases for tables.
->joinInner('t2', 'textprefix#test2', 't2.test_id = t1.id AND t2.field1 = ?', 123)
->joinLeft('t3', 'textprefix#test3', 't3.test_id = t1.id')
->joinRight('t4', 'textprefix#test4', 't4.test_id = t1.id')
->orderBy('t1.field1')
->orderBy('t1.field2', 'DESC')
->limit(100)
->offset(50);

if($contition === true) {
	$select->where('t1.somefield = ?', $someValue);
}

$rows = $select->fetchRows();

foreach($rows as $row) {
	print_r($row);
}
```

### Insert

You can insert key-value-arrays with `addAll`, `updateAll`, `addOrUpdateAll`. As the second parameter you can provide an array to specify the only fields to consider. 

```PHP
$mysql->insert()
->into('test')
->addOrUpdateAll($data, ['field1', 'field2', 'field3'])
->add('created_at=NOW()')
->addOrUpdateExpr('updated_at=NOW()')
->run();
```

There is also an option to build an `INSERT INTO ... SELECT ... FROM ... ON DUPLICATE KEY UPDATE ...`

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
