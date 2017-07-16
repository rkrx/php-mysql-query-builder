# Optional conditions

Sometimes you want to set conditions based on dynamic data. One example would be a grid-list with dynamic filters for certain columns. Following the example below, you can predefine _optional conditions_ which will execute when the corresponding data in the `$filter`-Array is present. 

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
->where(new DBExprFilter('t.name = ?', $filter, 'name'))
->where(new DBExprFilter('t.date >= ?', $filter, 'date.start')) // Key in dot-notation
->where(new DBExprFilter('t.date <= ?', $filter, ['date', 'end'])) // Key in array-notation
```

You can also define validation rules to only match certain data in `$filter`.

[Back](../README.md)
