# Update

```PHP
$mysql->update()
->table('t1', 'test1')
->joinLeft('t2', 'test2', 't1.id = t2.test_id')
->setAll($data)
->where("t1.field1 = ? OR t2.field2 > ?", 1, 10)
->where("field IN (?)", [1, 2, 3, 4, 5, 6])
->run();
```

## Basic principle

- `update()` returns a `RunnableUpdate`; `run()` returns the number of affected rows as an int.
- Multiple tables are supported via JOINs; mass assignments with `setAll` are intentionally disabled in that case.

## Tables and aliases

- `table($alias, $name)` sets the table name and optional alias; a single parameter is interpreted as the table name.
- Alias replacement works like it does for selects. For example, a schema alias such as `travis#table` is resolved through the AliasRegistry.

## Setting fields

- `set('field', $value)` generates ``field = '...'`` with automatic quoting.
- `setDefault('field')` writes `field = DEFAULT`.
- `setExpr('expr', ...$args)` allows raw expressions with placeholders, for example `setExpr('updated_at = NOW()')` or `setExpr('score = score + ?', 5)`.

## Mass assignment

- `setAll(array $data, ?array $allowedFields = null)` adds many fields at once.
- If `allowedFields` is set, only those fields are applied.
- Without `allowedFields`, only columns returned by `getTableFields` for the selected table are considered. This protects against typos.
- With more than one table, `setAll` throws an exception to avoid ambiguous assignment.

## WHERE, ORDER, LIMIT, OFFSET

- Same as the select builder: `where()`, `orderBy()`, `limit()`, and `offset()` are available.
- `where()` supports placeholder arrays and automatically generates IN clauses for array values.

## JOINs

- `joinInner`, `joinLeft`, and `joinRight` work like they do in the select builder, including alias-based table names.
- This is useful for updates through JOINs, for example setting or filtering target-table values based on joined tables.

## Executing vs. preparing

- `run($params = [])` builds the SQL and calls `exec` on the database system.
- `prepare()` returns a `DDLRunnable`, allowing prepared execution through a later `run()` call. This is useful in tests and mocks.

[Back](../README.md)
