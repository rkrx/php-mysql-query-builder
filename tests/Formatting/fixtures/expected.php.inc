<?php

namespace FormattingFixture;

use Kir\MySQL\Builder\PreparedSelect;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Builder\Select;
use Kir\MySQL\Database;

class ForeignBuilder {
	public function select(): self { return $this; }
	public function field(string $field): self { return $this; }
	public function fields(array $fields): self { return $this; }
	public function from(string $table): self { return $this; }
	public function where(string $condition): self { return $this; }
}

class Repository {
	public function __construct(protected Database $connection) {}
	protected function database(): Database { return $this->connection; }
	public function query(): RunnableSelect { return $this->database()->select()
		->field('id')
		->from('products'); }
}

class InheritedRepository extends Repository {
	public function rows(): array {
		return $this->query()
			->where('active = ?', 1)
			->prepare()
			->fetchRows();
	}
}

function basic(Database $db): array {
	return $db->select()
		->fields(['id' => 'c.id', 'name' => 'c.name'])
		->from('c', 'categories')
		->fetchRows();
}

function foreign(ForeignBuilder $db): ForeignBuilder {
	return $db->select()->fields(['id' => 'c.id', 'name' => 'c.name'])->from('categories');
}

function unknown($db) {
	return $db->select()->fields(['id' => 'c.id', 'name' => 'c.name'])->from('categories');
}

function ambiguous(Select|ForeignBuilder $query) {
	return $query->where('active = 1')->field('id');
}

function fromExternalFactory(Database $db): array {
	return ExternalFactory::create($db)
		->field('id')
		->from('products')
		->prepare()
		->fetchRows();
}

function existing(Select $query, PreparedSelect $prepared): array {
	$query
		->where('active = ?', 1)
		->orderBy('id');
	$query->where('paid = ?', 1);
	return $prepared
		->bindValue('active', 1)
		->fetchRows();
}

function bindings(Database $db, int $shopId, int $languageId): array {
	return $db->select()
		->field('id')
		->from('p', 'products')
		->joinLeft('translation', 'product_translations', 'translation.product_id = p.id AND translation.lang_id = ?',
			$languageId,
		)
		->where('p.shop_id = ? AND p.visibility_flag = ? AND p.stock_status = ? AND p.deleted = ?',
			$shopId,
			'public',
			'available',
			0,
		)
		->fetchRows();
}

function expressions(Database $db, string $long): array {
	return $db->select()
		->field('id')
		->from('p', 'products')
		->joinLeft('translated_product_category', 'product_category_translations',
			'translated_product_category.category_id = p.category_id AND translated_product_category.lang_id = ?',
			1,
		)
		->where(
			'this_expression_alone_is_intentionally_longer_than_the_configured_line_limit_and_must_remain_an_unmodified_SQL_string = ?',
			$long,
		)
		->fetchRows();
}

function arrays(Database $db): Select {
	return $db->select([
		'id' => 'p.product_id',
		'title' => 'p.product_title',
		'description' => 'p.product_description',
		'category' => 'p.category_id',
	])
		->from('p', 'products')
		->fields([
			'id' => 'p.product_id',
			'name' => 'p.product_name',
		]);
}

function subqueries(Database $db): array {
	return $db->select()
		->field('pending.id')
		->from(
			'pending',
			$db->select()
				->field('id')
				->from('orders')
				->where('status = ?', 'OPEN'),
		)
		->with(
			'extra',
			$db->select()
				->field('id')
				->from('products'),
		)
		->fetchRows();
}

function conditions(Database $db, bool $enabled): bool {
	if(
		$db->select()
			->field('id')
			->from('orders')
			->fetchValue() !== null
		&& $enabled
	) {
		return true;
	}
	return $enabled
		&& ($db->select()
			->field('id')
			->from('products')
			->fetchValue() !== null
			|| !$enabled);
}

function comments(Database $db): array {
	return $db->select()
		->fields([ // field mapping
			'id' => 'p.id', // identifier
			// visible label
			'name' => 'p.name', /* keep this */
		]) // source table follows
		->from('p', 'products')
		->where( /* condition */
			'p.active = ?',
			1, // bound value
		)
		->fetchRows();
}

function nullsafe(?Database $db): ?array {
	return $db?->select()
		?->field('id')
		?->from('products')
		?->fetchRows();
}

function callback(Database $db): array {
	return $db->select()
		->field('id')
		->from('products')
		->fetchRows(
			function (array $row) use ($db) {
				$row['exists'] = $db->select()
					->field('id')
					->from('orders')
					->where('id = ?', $row['id'])
					->fetchValue();
				return $row;
			},
		);
}

function named(Database $db): array {
	return $db->select()
		->field(expression: 'id')
		->from(alias: 'p', table: 'products')
		->where(
			expression: 'p.long_visibility_flag = ? AND p.long_stock_status = ? AND p.long_deleted_flag = ?',
			args: [1, 2, 3],
		)
		->fetchRows();
}

function insert(Database $db): int {
	return $db->insert()
		->into('products')
		->addAll(['id' => 1, 'label' => 'Sample'])
		->addOrUpdateExpr('updated_at = NOW()')
		->prepare()
		->run();
}

function updateAndDelete(Database $db): void {
	$db->update()
		->table('p', 'products')
		->set('active', 1)
		->where('p.id = ?', 3)
		->run();
	$db->delete()
		->from('products')
		->where('id = ?', 4)
		->run();
}

function trailingComment(Database $db): Select {
	return $db->select()
		->field('id') // keep the chain comment
		->from('products');
}

function specialArguments(Database $db, array $values, string $method): void {
	$db->select()
		->fields(['literal=>key' => 'p.id', 'nested' => ['a' => 1, 'b' => ['c' => 2]]])
		->from('p', 'products');
	$db->select()
		->field('id')
		->from('products')
		->where(
			'p.a_very_long_column_name = ? AND p.another_very_long_column_name = ? AND p.third_long_column_name = ?',
			...$values,
		);
	$query = $db->select();
	$query->{$method}('id')->where('active = ?', 1);
	$callable = $query->where(...);
}

function parenthesized(Database $db): Select {
	return ($db->select()
		->field('id'))
		->from('products');
}

function literal(Database $db): Select {
	return $db->select()
		->field(
			<<<SQL
first line
    second line
SQL,
		)
		->from('products');
}

function aliasedTable(Database $db): Select {
	return $db->select()
		->joinLeft('a_very_long_but_still_reasonable_table_alias', 'a_very_long_but_still_reasonable_table_name',
			'1 = 1',
		);
}

function arrayWidth120(Database $db): Select {
	return $db->select()
		->fields(['xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx']);
}

function arrayWidth121(Database $db): Select {
	return $db->select()
		->fields([
			'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
		]);
}

function unrelatedVoid(string $expected, $actual): void {}

function unrelatedCalls(array $rows): void {
	unrelatedVoid('this_is_a_very_long_expected_literal_that_should_not_cause_the_unrelated_function_call_to_be_formatted_or_reindented', $rows);
	\PHPUnit\Framework\Assert::assertSame('another_very_long_expected_literal_in_an_assertion_that_must_remain_exactly_as_written_in_the_original_source', $rows);
}

function neverReturns(string $message): never { throw new \RuntimeException($message); }

function unrelatedNever(): void {
	neverReturns('this_very_long_message_has_nothing_to_do_with_any_query_builder_and_must_not_be_reformatted_by_the_project_specific_rule');
}

function aliasPairOnContinuation(Database $db): Select {
	return $db->select()
		->joinLeft(
			'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'tttttttttttttttttttttttttttttttttttttttttttttttttt',
			'1=1',
		);
}

function loopsAndWrappers(Database $db, bool $enabled): void {
	while(
		$db->select()
			->field('id')
			->from('products')
			->fetchValue()
	) { break; }
	do { $db->delete()
		->from('products')
		->where('id = ?', 1)
		->run(); } while(
		$db->select()
			->field('id')
			->from('products')
			->fetchValue()
	);
	do {} while(
		$db->select()
			->field('id')
			->from('products')
			->fetchValue()
	);
	foreach($db->select()
		->field('id')
		->from('products')
		->fetchRows() as $row) {}
	for($i = 0; $i < 2
		&& $db->select()
			->field('id')
			->from('products')
			->fetchValue(); ++$i) {}
	$value = $enabled ? $db->select()
		->field('id')
		->from('products')
		->fetchValue() : null;
	$value = $db->select()
		->field('id')
		->from('products')
		->fetchRow()['id'] ?? null;
	if(
		// query guard
		$db->select()
			->field('id')
			->from('products')
			->fetchValue()
	) {}
	if(
		($enabled /* keep operator comment */ && $db->select()
			->field('id')
			->from('products')
			->fetchValue())
	) {}
}

function factoryWidth120(Database $db): Select {
	return $db->select(['yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy']);
}

function factoryWidth121(Database $db): Select {
	return $db->select([
		'yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy',
	]);
}

function spaceIndented(Database $db): Select {
	return $db->select()
		->field('id')
		->from('products');
}

function longArrayEntry(Database $db): Select {
	return $db->select()
		->fields([
			'keykeykeykeykeykeykeykeykeykeykeykeykeykeykeykeykey' =>
				'valuevaluevaluevaluevaluevaluevaluevaluevaluevaluevaluevaluevaluevalue',
		]);
}

function constructed(Database $db): Select {
	return (new \Kir\MySQL\Databases\MySQL\MySQLRunnableSelect($db))
		->field('id')
		->from('products');
}

function brokenFactory(Database $db): Select {
	return $db->select()
		->field('id')
		->from('products');
}
