# Select

This page covers many concepts on how to retrieve data using the query builder. But there are still a lot of tricks how to use the query builder, which are not documented on this page yet.

Feel free to submit a PR and improve something about this site.

## Fields

```php
use Kir\MySQL\Builder\Expr\DBOrderSpec;
use Kir\MySQL\Databases\MySQL;

$pdo = new PDO(/* ... */);
$db = new MySQL($pdo);

// One method call for each field specifications
$rows = $db->select()
->field('t.field1')
->field('t.field2', 'alias')
->from('t', 'some_table')
->fetchRows();

// Field specifications in an array notation
$rows = $db->select()
->fields([
	'alias1' => 't.field1',
	'alias2' => 't.field2'
])
->from('t', 'some_table')
->fetchRows();
```

## Receiving data

```php
use Kir\MySQL\Builder\Expr\DBOrderSpec;
use Kir\MySQL\Databases\MySQL;

$db = new MySQL(new PDO(/* ... */));

$select = $db->select()
->field('t.field1')
->field('t.field2')
->from('t', 'test');

// Get multiple rows as a Key/Value-Array (like PDO::FETCH_ASSOC)
// Result is guaranteed to be an array<int, array<string, string>>.
// Might throw an exception.
$rows = $select->fetchRows();

// Get multiple rows with numeric column indexes (like PDO::FETCH_NUM).
$indexedRows = $select->fetchIndexedRows();

// Get the first column of every row.
$values = $select->fetchValues();

// Get one row as a Key(=Fieldnames)/Value(=Values)-Array (like PDO::FETCH_ASSOC)
// Result is guaranteed to be an array<string, string> or an empty array.
// Might throw an exception.
$row = $select->fetchRow();

// Get the value of the first column of the result-set.
// No value means `null`.
$value = $select->fetchValue();
```

`fetchArray()` is deprecated; use `fetchValues()` instead.

### Prepared selects

`prepare()` renders and prepares the select once. The returned `PreparedSelect`
contains only value-binding and fetch methods and can execute the prepared PDO
statement more than once. Values already bound to the select are captured when
`prepare()` is called and can be changed on the prepared select.

```php
$prepared = $db->select()
	->field('t.field1')
	->from('t', 'test')
	->where('t.id=:id')
	->prepare();

$firstRow = $prepared->bindValue('id', 1)->fetchRow();
$secondRow = $prepared->bindValue('id', 2)->fetchRow();
```

### Keep the original database types

Current PDO MySQL versions return integers and floating-point values as native PHP
types when `PDO::ATTR_STRINGIFY_FETCHES` is disabled. `DECIMAL` values and integers
outside the PHP integer range remain strings so their value is not truncated.
`setPreserveTypes()` is retained for legacy configurations; it reconstructs numeric
types from column metadata, including converting `DECIMAL` to `float`, which can
lose precision.

## Optional conditions

Sometimes you want to set conditions based on dynamic data. One example would be a grid-list with dynamic filters for certain columns. Following the example below, you can predefine _optional conditions_ which will execute when the corresponding data in the `$filter`-Array is present.

```PHP
use Kir\MySQL\Databases\MySQL;
use Kir\MySQL\Builder\Expr\DBExprFilter;
use Kir\MySQL\Builder\Value\DBOptionalValue;

$filter = [
	'name' => 'Peter',
	'date' => [
		'start' => '2016-05-01',
		'end' => '2016-05-31',
	],
];

$query = (new MySQL(new PDO(/* ... */)))
	/* ... */
	->from('t', 'test')
	/* ... */
	->where(new DBExprFilter('t.name = ?', $filter, 'name'))
	->where(new DBExprFilter('t.date >= ?', $filter, 'date.start'))    // Key in dot-notation
	->where(new DBExprFilter('t.date <= ?', $filter, ['date', 'end'])) // Key in array-notation
	->limit(new DBOptionalValue($filter, ['count']))
	->offset(new DBOptionalValue($filter, ['offset']))
	->fetchRows();
```

You can also define validation rules to only match certain data in `$filter`.

## Grouped conditions

Multiple calls to `where()` or `having()` are connected with `AND` by default. Use `OrExpr` and `AndExpr` when a condition needs an explicit grouped expression.

```PHP
use Kir\MySQL\Builder\Expr\DBCondition;
use Kir\MySQL\Builder\Expr\DBExprFilter;
use Kir\MySQL\Builder\Expr\OrExpr;
use Kir\MySQL\Databases\MySQL;

$filter = ['ean' => '4000000000000'];

$rows = (new MySQL(new PDO(/* ... */)))
	->select()
	->field('t.category_id')
	->field('COUNT(*)', 'product_count')
	->field('SUM(t.stock)', 'stock_sum')
	->from('t', 'products')
	->where('t.active=?', true)
	->where(new OrExpr(
		new DBCondition('t.name LIKE ?', '%desk%'),
		new DBCondition('t.reference=?', 'ABC-123'),
		new DBExprFilter('t.ean=?', $filter, 'ean'),
	))
	->groupBy('t.category_id')
	->having(new OrExpr(
		new DBCondition('COUNT(*) > ?', 10),
		new DBCondition('SUM(t.stock) IS NULL'),
	))
	->fetchRows();
```

This produces a condition shape like:

```sql
WHERE
	(t.active=1)
	AND
	((t.name LIKE '%desk%') OR (t.reference='ABC-123') OR (t.ean='4000000000000'))
HAVING
	((COUNT(*) > 10) OR (SUM(t.stock) IS NULL))
```

Use `DBCondition` inside grouped expressions when the SQL fragment has placeholder arguments. Optional filters can be used in a group as well: if a `DBExprFilter` has no valid value, that part is skipped. If all parts of a group are skipped, the complete grouped condition is omitted.

`AndExpr` can be used directly or nested inside `OrExpr`:

```PHP
use Kir\MySQL\Builder\Expr\AndExpr;
use Kir\MySQL\Builder\Expr\DBCondition;
use Kir\MySQL\Builder\Expr\OrExpr;

$select
	->where(new OrExpr(
		new AndExpr(
			new DBCondition('t.type=?', 'product'),
			new DBCondition('t.active=?', true),
		),
		new DBCondition('t.force_visible=?', true),
	));
```

The same grouped condition objects can be used wherever query conditions are supported, including `where()` on select, update and delete builders, and `having()` on select builders.

## Sorting from data

```php
use Kir\MySQL\Databases\MySQL;
use Kir\MySQL\Builder\Expr\DBOrderSpec;

$db = new MySQL(new PDO(/* ... */));

// [alias => dir, alias => dir, ...]
$_GET = ['field3' => 'ASC', 'field1' => 'ASC', 'field2' => 'ASC', 'field4' => 'ASC'];

$rows = $db->select()
	->field('t.field1')
	->field('t.field2')
	->from('t', 'test')
	->orderBy(new DBOrderSpec([
			'field1' => 't.field1',           // alias => db-expression
			'field2' => 'REVERSE(t.field2)',  // alias => db-expression
			'field4' => 't.field2'            // alias => db-expression
		], $_GET))
	->fetchRows();
```

*⚠ Any aliases specified in the search instructions but for which no DB expression has been defined will result in an exception. It is better that it will bang in such a case instead of just not working.*

## Common table expressions

Use `with()` to add a common table expression to a select. The CTE body can be
another select or a raw SQL string. Multiple calls are rendered in their call
order under one `WITH` clause.

```php
$activeProducts = $db->select()
	->field('p.id')
	->field('p.name')
	->from('p', 'products')
	->where('p.active=?', true);

$rows = $db->select()
	->with('active_products', $activeProducts)
	->field('p.id')
	->field('p.name')
	->from('p', 'active_products')
	->fetchRows();
```

Recursive CTEs use `withRecursive()`. Its optional third argument declares the
column names exposed by the CTE.

```php
$numbers = $db->select()
	->field('1', 'n')
	->unionAll(
		$db->select()
			->field('n + 1', 'n')
			->from('numbers')
			->where('n < 5')
	);

$values = $db->select()
	->withRecursive('numbers', $numbers, ['n'])
	->field('n')
	->from('numbers')
	->fetchValues();
```

## Some examples

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

## Complex select

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
