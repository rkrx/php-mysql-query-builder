# Query Builder formatting

These rules apply to PHP code and PHP documentation examples that use the Query Builder. They define how to format method chains, including their execution and fetch calls.

The includable [CS-Fixer configuration](cs-fixer.md) enforces these rules using PHPStan's project-wide type information. It does not identify queries by variable names or by method names alone.

## Indentation and line breaks

- Use tabs for indentation, with a display width of four, as specified in [`.editorconfig`](../.editorconfig).
- Keep the initial builder call (`select()`, `insert()`, `update()`, or `delete()`) on the starting line, together with any assignment or `return`.
- Keep the receiver expression and factory name together, including forms such as `$this->db()->select()` or a typed query factory. The factory's arguments may span multiple lines.
- Put every subsequent method call on its own line, starting with `->`. This also applies to short chains and terminal calls such as `run()`, `prepare()`, `addRows()`, and `fetch*()`.
- Indent all calls in the same chain by exactly one tab relative to the starting line. Do not increase the indentation with each successive call or align it using spaces.
- Keep the semicolon at the end of the final call, not on a separate line.

```php
$rows = $db->select()
	->field('order_id')
	->from('order_inbox')
	->where('status = ?', 'OPEN')
	->orderBy('received_at')
	->fetchRows();

$db->delete()
	->from('order_versions')
	->where('order_id = ?', $orderId)
	->run();
```

Property access and the initial factory call stay together: `$this->db->select()` is a valid starting line. The one-call-per-line rule applies to the subsequent Query Builder calls, not to the property access in that expression.

Do not compress the same query into a single line or combine several calls on a continuation line:

```php
// Incorrect: multiple Query Builder calls on each line.
$rows = $db->select()->field('order_id')->from('order_inbox')
	->where('status = ?', 'OPEN')->orderBy('received_at')->fetchRows();
```

## Existing builders and surrounding blocks

When chaining multiple calls on an existing builder or prepared statement, put the variable on the starting line and indent the calls by one tab. A standalone call without a chain can remain on one line.

```php
$query
	->where('status = ?', 'OPEN')
	->orderBy('received_at');

$rows = $prepared
	->bindValue('status', 'OPEN')
	->fetchRows();

$query->where('is_paid = ?', 1);
$rows = $query->fetchRows();
```

Add the chain indentation to the existing indentation of a method, closure, or control-flow block:

```php
function pendingIds(Database $db): array {
	return $db->select()
		->field('order_id')
		->from('order_inbox')
		->where('status = ?', 'OPEN')
		->fetchValues();
}
```

## Arrays and multiline arguments

- An argument list or inline array may remain on one line if the complete call line, including its indentation and closing punctuation, has at most 120 characters. Each tab counts as four characters. Count Unicode characters rather than UTF-8 bytes.
- Keep an already multiline array multiline, even when it could fit on one line. Apply the same array rules to factory arguments, `fields()`, bulk data, masks, bound arrays, and nested arrays inside query arguments. A separately declared array outside a query expression is outside the fixer's scope.
- For a multiline array, put each entry on its own line, one tab deeper than the line containing the opening bracket. Use a trailing comma after the last entry.
- Use one space around `=>`; do not align entries with padding spaces.
- Align the closing `])` with the method call's `->`, then resume the chain at that same level on the next line.
- For multiline argument lists, align the closing `)` with the method call, and use a trailing comma. Arguments on continuation lines are indented one additional tab.

```php
$db->insert()
	->into('order_inbox')
	->addAll([
		'order_id' => $orderId,
		'status' => 'OPEN',
		'received_at' => $receivedAt,
	])
	->run();

$rows = $db->select()
	->fields(['o.order_id', 'v.version_id'])
	->from('o', 'order_inbox')
	->joinLeft('v', 'order_versions', 'v.order_id = o.order_id AND v.notify_change = ?', 1)
	->fetchRows();
```

Nested arrays add one indentation level per nesting level. Closing brackets return to the indentation of the corresponding opening entry or method call.
If an associative entry's key and value together exceed 120 characters, move the value below `=>` and indent it one additional tab. Preserve the value itself.

### Grouping long argument lists

For JOIN methods, prefer alias, table, and expression together on the opening line. If the whole call exceeds 120 characters, apply these steps in order:

1. Move the bound values to continuation lines, one value per line. Keep the alias, table, and expression on the opening line if that line fits.
2. If necessary, also move the expression to its own continuation line. Keep alias and table together on the opening line.
3. If the opening line is still too long, move alias and table together to one continuation line.
4. Split alias and table onto separate lines only if their combined continuation line also exceeds 120 characters.

```php
$db->select()
	->field('p.id')
	->from('p', 'products')
	->joinLeft('translation', 'product_translations', 'translation.product_id = p.id AND translation.lang_id = ?',
		$languageId,
	)
	->joinLeft('translated_product_category', 'product_category_translations',
		'translated_product_category.category_id = p.category_id AND translated_product_category.lang_id = ?',
		$languageId,
	)
	->fetchRows();
```

For `where()`, `having()`, and assignment-expression methods such as `addExpr()` and `setExpr()`, keep the expression on the opening line when possible, and move bound values first. Once moved, each bound value has its own line. The same preference for keeping the expression applies to `field()` and `orderBy()`.

Short calls keep their arguments together. When a call has a multiline subquery, closure, or several arguments including a multiline array, use the fully multiline argument layout. Comments are preserved and may prevent otherwise possible grouping. Long named argument lists use one argument per continuation line without changing their order. Unpacked arguments remain unpacked.

String literals, SQL expressions, identifiers, and receiver expressions are never split or rewritten to satisfy the width limit. An individual unbreakable part may therefore exceed 120 characters. Heredoc and nowdoc contents remain unchanged.

## Subqueries

Apply the same rules independently to each query. In an inline subquery, its factory call starts at the argument indentation; its continuation calls are one tab deeper. The outer chain resumes at its original indentation.

```php
$rows = $db->select()
	->field('pending.order_id')
	->from(
		'pending',
		$db->select()
			->field('order_id')
			->from('order_inbox')
			->where('status = ?', 'OPEN'),
	)
	->orderBy('pending.order_id')
	->fetchRows();
```

A named subquery variable can make larger queries easier to read. Its chain follows the same rules.

The same layout applies to `with()`, `withRecursive()`, `union()`, and `unionAll()`. Each nested query has its own starting indentation. Callback bodies keep their structure; queries inside callbacks follow the query rules independently.

## Queries in conditions

Keep queries readable inside conditions too. Put the condition on a separate line, indent the query's continuation calls one further level, and align the closing condition parenthesis with `if`.

```php
if (
	$db->select()
		->field('version_id')
		->from('order_versions')
		->where('version_id = ?', $versionId)
		->fetchValues() !== []
) {
	return false;
}
```

For compound conditions, put each boolean operand on its own line, with `&&` or `||` at the start of continuation lines. Indent a query chain one tab relative to the line that starts that query.

```php
$notify = !$created
	&& $status === 'PROCESSED'
	&& $db->select()
		->field('version_id')
		->from('order_versions')
		->where('order_id = ?', $orderId)
		->where('payload_sha256 = ?', $checksum)
		->fetchValues() === [];
```

When changing formatting, preserve execution order, parentheses, and short-circuit behavior. Extract a query into a preceding statement only if doing so preserves when and whether the query runs.

The fixer changes whitespace and trailing commas only. It does not extract queries, reorder arguments, add execution calls, or remove grouping parentheses. Queries inside casts, array access, ternaries, null coalescing, loop headers, and surrounding function calls retain those enclosing expressions. Nullsafe operators remain nullsafe; first-class callables keep their placeholder syntax.

[Back to the conversion guide](../AGENTS.md)
