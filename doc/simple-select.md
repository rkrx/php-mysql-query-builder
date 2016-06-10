# Select

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

[Back](../README.md)
