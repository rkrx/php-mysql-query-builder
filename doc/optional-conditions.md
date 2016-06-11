# Optional conditions

```PHP
$filter = [
	'name' => 'Peter',
	'date' => [
		'start' => '2016-05-01',
		'end' => '2016-05-31',
	],
];

$req = new RequiredFilterMap($filter);
$opt = new OptionalFilterMap($filter);

$query = $db
->from('t', 'test')
->where($req('t.name=?', $filter, 'name'))
->where($opt('t.date >= ?', $filter, 'date.start')) // Key in dot-notation
->where($opt('t.date <= ?', $filter, ['date', 'end'])) // Array-Key
```


[Back](../README.md)
