# Insert

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

[Back](../README.md)
