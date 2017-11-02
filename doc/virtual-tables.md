# Virtual tables

## Unparameterized virtual tables

First define virtual tables somewhere in your bootstrap:

```php
use Kir\MySQL\Databases\MySQL;

$db = new MySQL($pdo);

$vt1 = $db->select()
->field('a.field1')
->from('a', 'tableA');

$db->getVirtualTables()->add('virt_table1', $vt1);

// Lazy evaluated
$db->getVirtualTables()->add('virt_table2', function () {
	return $db->select()
	->field('a.field1')
	->from('a', 'tableA');
});
```

Then use it as needed:

```php
$query = $db->select()
->field('t.field1')
->field('vt1.fieldN')
->field('vt2.fieldN')
->from('t', 'test')
->joinInner('vt1', 'virt_table1', 'vt1.field1=t.field1')
->joinInner('vt2', 'virt_table2', 'vt2.field2=t.field2');
```

## Parameterized virtual tables

When you need parameterized sub-selects, you can use the Helperclass `Kir\MySQL\Tools\VirtualTable` to add parameters to a table-name:

Definition:

```php
use Kir\MySQL\Databases\MySQL;

$db = new MySQL($pdo);

$vt1 = $db->select()
->field('a.field1')
->from('a', 'tableA');

$db->getVirtualTables()->add('virt_table1', $vt1);

// Lazy evaluated; parameterized
$db->getVirtualTables()->add('virt_table2', function (array $args) {
	return $db->select()
	->field('a.field1')
	->from('a', 'tableA')
	->where(new DBExprFilter('a.active=?', $args, 'active'));
});
```

Then use it as needed:

```php
$query = $db->select()
->field('t.field1')
->field('vt1.fieldN')
->field('vt2.fieldN')
->from('t', 'test')
->joinInner('vt1', 'virt_table1', 'vt1.field1=t.field1')
->joinInner('vt2', new VirtualTable('virt_table2', ['active' => 1]), 'vt2.field2=t.field2');
```

[Back](../README.md)
