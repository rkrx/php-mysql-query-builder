# Optional conditions

```PHP
$filter = [
	'name' => 'Peter',
	'date' => [
		'start' => '2016-05-01',
		'end' => '2016-05-31',
	],
];

$query = $db
->from('t', 'test')
->where(new OptionalDBFilterMap('t.name=?', $filter, 'name'))
->where(new OptionalDBFilterMap('t.date >= ?', $filter, 'date.start')) // Key in dot-notation
->where(new OptionalDBFilterMap('t.date <= ?', $filter, ['date', 'end'])) // Array-Key
```

[Back](../README.md)
