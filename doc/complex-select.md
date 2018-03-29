# Complex select

```php
$dateStart = '2016-05-01';
$dateEnd = '2016-05-31';

$tableA = $db->select()
->field('a.field1')
->field('a.field2')
->from('a', 'table_a')
->where('a.date BETWEEN ? AND ?', $dateStart, $dateEnd);

$tableB = $db->select()
->field('b.field1')
->field('b.field2')
->from('b', 'table_b')
->where('b.date BETWEEN ? AND ?', $dateStart, $dateEnd);

$tableC = $db->select()
->field('t.field1')
->field('t.field2')
->from('t', 'table_c')
->where('t.date BETWEEN ? AND ?', $dateStart, $dateEnd);

echo $db->select()
->from('t',
	$db->select()
	->field('a.field1')
	->field('COALESCE(b.field2, a.field2)', 'field2')
	->from('a', $tableA)
	->joinLeft('b', $tableB, 'b.id=a.id')
	->where('NOT ISNULL(a.field1)')
	->union($tableC)
);
```

[Back](../README.md)
