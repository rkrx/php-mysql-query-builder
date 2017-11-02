# Virtual tables

First define virtual tables somewhere in your bootstrap:

```php
use Kir\MySQL\Databases\MySQL;

$db = new MySQL($pdo);

$vt1 = $db->select()
->field('a.field1')
->from('a', 'tableA');

$vt2 = $db->select()
->field('a.field1')
->from('a', 'tableA');

$db->getVirtualTables()->add('virt_table1', $vt1);
$db->getVirtualTables()->add('virt_table2', $vt2);
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

[Back](../README.md)
