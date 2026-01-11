# Temporary tables

Sometimes you have complex subqueries that are expensive to calculate and are used multiple times or need to be materialized for performance reasons (e.g., to create an index on the intermediate result).

MariaDB/MySQL does not always materialize subqueries efficiently, and `WITH` clauses (CTEs) are not always materialized. In these cases, explicit temporary tables are a robust solution.

This library allows you to easily convert a `SELECT` query into a temporary table and use it in subsequent queries.

## Usage

You can use the `temporary()` method on any `Select` builder. This immediately executes a `CREATE TEMPORARY TABLE ... AS SELECT ...` query and returns a `RunnableTemporaryTable` object.

This object implements `Stringable` and returns the name of the temporary table when converted to a string, so you can pass it directly to `from()` or `join()`.

### Example

```php
// 1. Define the complex query
$result = $db->select()
    ->from('orders', 'o')
    ->fields([
        'customer_id' => 'o.customer_id',
        'total_amount' => 'SUM(o.amount)'
    ])
    ->groupBy('o.customer_id')
    ->temporary([
        'customer_id' => 'INT', 
        'total_amount' => 'DECIMAL(10,2)',
        'PRIMARY KEY (customer_id)'
    ], [
        'engine' => 'MEMORY' // Optional: Specify engine
    ]);

// $result is now a RunnableTemporaryTable instance. 
// The table 'tmp_...' has been created in the database.

// 2. Use the temporary table in another query
$rows = $db->select()
    ->from('customers', 'c')
    ->joinInner('t', $result, 'c.id = t.customer_id')
    ->fields(['c.name', 't.total_amount'])
    ->fetchRows();

// 3. Clean up
$result->release();
```

### Method Signature

```php
public function temporary(array $schema, array $options = []): RunnableTemporaryTable;
```

*   **$schema**: An array defining the columns and indexes of the temporary table. Keys are column names (or integer indices for raw definitions), values are SQL type definitions.
*   **$options**: An array of options. Currently supported:
    *   `engine`: The storage engine to use (e.g., 'MEMORY', 'InnoDB').

The `temporary()` method returns a `RunnableTemporaryTable` instance, which has the following methods:

*   `getName(): string`: Returns the name of the generated temporary table.
*   `release(): $this`: Drops the temporary table.
