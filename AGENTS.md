# SQL and PHP Query Builder Conversion Guide

This guide is designed to help AI systems translate SQL queries into PHP Query Builder syntax and vice versa, covering essential SQL clauses and concepts.

## The SELECT Statement

### FROM Clause

Consider the following SQL statement:

```sql
SELECT * FROM products p
```

The Query Builder is written as follows to generate the displayed SQL exactly as shown:

```php
$db->select()
	->field('*')
	->from('p', 'products');
```

#### Using an Alias in the FROM Clause:

There are generally two ways to write this. In the syntax with two parameters, the first parameter is always the alias, and the second parameter is always the table name:

```sql
/* ... */ FROM products p
```

This is equivalent to

```php
/* ... */ ->from('p', 'products');
```

#### Using the FROM Clause Without an Alias:

If only one parameter is used, it will be interpreted as the table name:

```sql
/* ... */ FROM products
```

This is equivalent to

```php
/* ... */ ->from('products');
```

### Using Table Fields

Translate table fields to match the following pattern:

```sql
SELECT
	a.id AS 'ID',
	a.ref AS 'Reference',
	b.name AS 'Name',
	a.description AS 'ProductDescription'
FROM
	products a
INNER JOIN
	product_desciptions b ON a.id = b.id
```

The alias of a field is used as the key in a PHP associative array, and the SQL expression is the value of the array.

```php
$fields = [
	'ID' => 'a.id',
	'Reference' => 'a.ref',
	'Name' => 'b.name',
	'ProductDescription' => 'a.description'
];
```

The Query Builder is written as follows to generate the displayed SQL exactly as shown:

```php
$db->select()
	->fields([
		'ID' => 'a.id',
		'Reference' => 'a.ref',
		'Name' => 'b.name',
		'ProductDescription' => 'a.description'
	])
	->from('a', 'products')
	->innerJoin('b', 'product_descriptions', 'a.id = b.id');
```

Here’s the enhanced document with additional sections explaining the use of LEFT JOIN, RIGHT JOIN, INNER JOIN, WHERE, HAVING, ORDER BY, LIMIT, OFFSET, and subselects in SQL, along with corresponding examples in PHP Query Builder.

### JOIN Clauses

#### INNER JOIN

`INNER JOIN` returns rows when there is a match in both tables. Example SQL:

```sql
/* ... */ INNER JOIN test2 t2 ON t2.test_id = t1.id AND t2.field1 = 123
```

PHP Query Builder equivalent:

```php
/* ... */ ->joinInner('t2', 'test2', 't2.test_id = t1.id AND t2.field1 = ?', 123)
```

#### LEFT JOIN

`LEFT JOIN` returns all rows from the left table, and matched rows from the right table. If there is no match, NULL values are returned for columns from the right table.

Example SQL:

```sql
LEFT JOIN test3 t3 ON t3.test_id = t1.id
```

PHP Query Builder equivalent:

```php
->joinLeft('t3', 'test3', 't3.test_id = t1.id')
```

#### RIGHT JOIN

`RIGHT JOIN` returns all rows from the right table, and matched rows from the left table. If there is no match, NULL values are returned for columns from the left table. Example SQL:

```sql
RIGHT JOIN test4 t4 ON t4.test_id = t1.id
```

PHP Query Builder equivalent:

```php
->joinRight('t4', 'test4', 't4.test_id = t1.id')
```

#### Using Subselects in JOINs

A subselect can be used as a virtual table in joins. Here’s an example with `RIGHT JOIN`:

```sql
RIGHT JOIN (SELECT t.id FROM table t WHERE (t.foreign_id=10)) t5 ON t5.test_id = t1.id
```

PHP Query Builder equivalent:

```php
$subSelect = function ($id) use ($mysql) {
	return $mysql->select()
		->field('t.id')
		->from('t', 'table')
		->where('t.foreign_id=?', $id);
};

$mysql->joinRight('t5', $subSelect(10), 't5.test_id = t1.id');
```

### WHERE Clause

The `WHERE` clause is used to filter records that meet certain conditions. Example SQL:

```sql
WHERE t1.field = 'value'
```

PHP Query Builder equivalent:

```php
$mysql->where('t1.field = ?', 'value');
```

The value can be a scalar value (including `null`) and can also be an array of values. The Query Builder will automatically translate value-arrays into an according `IN`-Clause.

### HAVING Clause

The `HAVING` clause is used to filter records after aggregation. It typically applies to grouped results. Example SQL:

```sql
HAVING customer_count > 10
```

PHP Query Builder equivalent:

```php
->having('customer_count > 10')
```

### ORDER BY Clause

The ORDER BY clause is used to sort the result set by one or more columns. Example SQL:

```sql
ORDER BY t1.field1 ASC, t1.field2 DESC
```

PHP Query Builder equivalent:

```php
->orderBy('t1.field1')
->orderBy('t1.field2', 'DESC')
```

### LIMIT Clause

The LIMIT clause specifies the maximum number of records to return. Example SQL:

```sql
LIMIT 100
```

PHP Query Builder equivalent:

```php
->limit(100)
```

### OFFSET Clause

The OFFSET clause is used in conjunction with LIMIT to skip a certain number of rows before beginning to return rows. Example SQL:

```sql
OFFSET 50
```

PHP Query Builder equivalent:

```php
->offset(50)
```

### Using Subselects

Subselects (or subqueries) are queries nested within another query, often used to retrieve data for specific conditions.

Example SQL for a subselect:

```sql
SELECT COUNT(*) AS customer_count FROM test1 t1
RIGHT JOIN (SELECT t.id FROM table t WHERE (t.foreign_id=10)) t5 ON t5.test_id = t1.id
```

PHP Query Builder equivalent with a subselect function:

```php
$subSelect = function ($id) use ($mysql) {
	return $mysql->select()
		->field('t.id')
		->from('t', 'table')
		->where('t.foreign_id=?', $id);
};

$select = $mysql->select()
	->field('COUNT(*)', 'customer_count')
	->from('t1', 'test1')
	->joinRight('t5', $subSelect(10), 't5.test_id = t1.id');
```

## The INSERT Statement

### Basic INSERT

Consider the following SQL statement:

```sql
INSERT INTO table (field1, field2) VALUES ('value1', 123)
```

The Query Builder is written as follows to generate the displayed SQL exactly as shown:

```php
$db->insert()
	->into('table')
	->add('field1', 'value1')
	->add('field2', 123)
	->run();
```

#### Using Expressions in INSERT

To include SQL expressions directly in the INSERT statement, use `addExpr`:

```sql
INSERT INTO table (created_at) VALUES (NOW())
```

PHP Query Builder equivalent:

```php
$db->insert()
	->into('table')
	->addExpr('created_at = NOW()')
	->run();
```

### UPSERT (INSERT ON DUPLICATE KEY UPDATE)

For scenarios where you want to insert a new row or update an existing row if a duplicate key is found, use `addOrUpdate` and `addOrUpdateExpr`:

```sql
INSERT INTO table (field1, field2, updated_at) VALUES ('value1', 'abc', NOW())
ON DUPLICATE KEY UPDATE field2 = VALUES(field2), updated_at = NOW()
```

PHP Query Builder equivalent:

```php
$db->insert()
    ->into('table')
    ->add('field1', 'value1')
    ->addOrUpdate('field2', 'abc')
    ->addOrUpdateExpr('updated_at = NOW()')
    ->run();
```

#### Using Expressions with Parameters in UPSERT

To use SQL functions with parameters in UPSERT scenarios, use `addOrUpdateExpr` with placeholders:

```sql
INSERT INTO table (hash_field) VALUES (MD5('some value to be hashed'))
ON DUPLICATE KEY UPDATE hash_field = MD5('some value to be hashed')
```

PHP Query Builder equivalent:

```php
$db->insert()
    ->into('table')
    ->addOrUpdateExpr('hash_field = MD5(?)', 'some value to be hashed')
    ->run();
```

### UPDATE Part in UPSERT

To specify assignments only in the `UPDATE` part during an UPSERT scenario, use `update` and `updateExpr`:

```sql
ON DUPLICATE KEY UPDATE updated_by = 2, update_count = update_count + 1
```

PHP Query Builder equivalent:

```php
$db->insert()
    ->into('table')
    ->update('updated_by', $userId)
    ->updateExpr('update_count = update_count + 1')
    ->run();
```

This guide provides a structured approach to building INSERT and UPSERT SQL statements using a PHP Query Builder, allowing for clean and maintainable code.

### Table Aliases in INSERT

Tables can use alias expansion like `schema#table`, which resolves via the alias registry. Example: `travis#test1` resolves to `travis_test.test1`.

```php
$db->insert()
	->into('travis#test1')
	->addExpr('last_update=NOW()');
// Yields:
// INSERT INTO
// 	travis_test.test1
// SET
// 	last_update=NOW()
```

### INSERT ... SELECT (Mass Insert)

Populate rows from a subselect. The field list is derived from the select’s field aliases/keys.

```php
$select = $db->select()
	->fields(['a' => 'b'])
	->from('oi', 'travis#test1')
	->where('1!=2');

$sql = $db->insert()
	->into('travis#test2')
	->from($select)
	->updateExpr('a = VALUES(a)');
// Yields:
// INSERT INTO travis_test.test2 (a)
//     SELECT
//         b AS `a`
//     FROM
//         travis_test.test1 oi
//     WHERE
//         (1!=2)
//     ON DUPLICATE KEY UPDATE
//         a = VALUES(a)
```

### Helper Methods for Many Fields

- `addAll(array $data, ?array $mask = null, ?array $exclude = null)`: Adds multiple `SET` assignments at once.
- `updateAll(array $data, ?array $mask = null, ?array $exclude = null)`: Adds multiple `ON DUPLICATE KEY UPDATE` assignments.
- `addOrUpdateAll(array $data, ?array $mask = null, ?array $exclude = null)`: Adds to both `SET` and `UPDATE` parts.

Examples:

```php
// Add a subset of fields
$db->insert()
	->into('travis#test1')
	->addAll(['field1' => 123, 'field2' => 456], ['field1']);
// SET `field1`=123

// Update a subset on conflict
$db->insert()
	->into('travis#test1')
	->add('field1', 123)
	->updateAll(['field1' => 123, 'field2' => 456], ['field1']);
// SET `field1`=123
// ON DUPLICATE KEY UPDATE `field1`=123

// Add and update all fields (respecting mask/exclude)
$db->insert()
	->into('travis#test1')
	->addOrUpdateAll(['field1' => 123, 'field2' => 456]);
// SET `field1`=123, `field2`=456
// ON DUPLICATE KEY UPDATE `field1`=123, `field2`=456
```

### Field Masking

Use `setMask(array $fieldNames)` to restrict which fields are emitted across `SET` and `ON DUPLICATE KEY UPDATE`.

```php
$db->insert()
	->into('test')
	->addOrUpdate('field1', 1)
	->addOrUpdate('field2', 2)
	->setMask(['field1']);
// Emits only `field1` in both SET and UPDATE
```

### IGNORE Inserts

Enable `INSERT IGNORE` by calling `setIgnore(true)`.

```php
$db->insert()
	->into('table')
	->setIgnore()
	->add('field', 1);
// INSERT IGNORE INTO ...
```

### Primary Key Handling and LAST_INSERT_ID

When a primary key is included in the insert and you want MySQL to return that key as the last insert id, call `setKey('id_field')`. This adds
`` `id_field` = LAST_INSERT_ID(id_field) `` to the `ON DUPLICATE KEY UPDATE` part and prevents updating the key via `updateAll`.

```php
$db->insert()
	->into('table')
	->setKey('id')
	->add('id', 100)
	->addOrUpdate('name', 'Alice');
// ON DUPLICATE KEY UPDATE
// 	`id` = LAST_INSERT_ID(id),
// 	`name`='Alice'
```

### Raw Expressions and Parameters

- `addExpr('expr', ...args)`, `updateExpr('expr', ...args)`, `addOrUpdateExpr('expr', ...args)` accept placeholders and values.
- Use `new DBExpr('NOW()')` (or other SQL) to inject unquoted expressions.

```php
use Kir\MySQL\Builder\DBExpr;

$db->insert()
	->into('test')
	->addExpr('a=?', 'a')            // a='a'
	->updateExpr('b=?', 'b')         // b='b'
	->addOrUpdateExpr('c=?', 'c')    // c='c' in both parts
;

$db->insert()
	->into('test')
	->addExpr('a=?', new DBExpr('NOW()'))      // a=NOW()
	->updateExpr('b=?', new DBExpr('NOW()'))   // b=NOW()
	->addOrUpdateExpr('c=?', new DBExpr('NOW()'));
```

### Executing vs. Rendering

- `run()` executes the insert and returns an integer (e.g., affected rows or last insert id depending on driver).
- `__toString()`/helper like `asString()` in tests returns the SQL for inspection.
