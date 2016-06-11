# Simple repository

```PHP
$pdo = new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8', 'root', null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$db = new \Kir\MySQL\Databases\MySQL($pdo);
$db->getAliasRegistry()->add('shop', 'shop__');
```

```PHP
class ProductAmountQuery {
	/** @var Database */
	private $db;

	/**
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		$this->db = $db;
	}

	/**
	 * @param array $criteria
	 * @return RunnableSelect
	 */
	public function getAll(array $criteria) {
		$req = new RequiredDBFilterMap($criteria);
		$opt = new OptionalDBFilterMap($criteria);
		return $this->db->select()
		->field('sp.pricelist_id')
		->field('sp.product_id')
		->field('sp.price')
		->from('sp', 'shop#product_pricelists')
		->where($req('sp.pricelist_id=?', 'pricelist.id'))
		->where($opt('sp.price = ?', 'pricelist.price.is'))
		->where($opt('sp.price >= ?', 'pricelist.price.min'))
		->where($opt('sp.price <= ?', 'pricelist.price.max'));
	}
}
```

```PHP
class ProductRepository {
	/** @var Database */
	private $db;
	/** @var ProductAmountQuery */
	private $amountQuery;

	/**
	 * @param Database $db
	 * @param ProductAmountQuery $amountQuery
	 */
	public function __construct(Database $db, ProductAmountQuery $amountQuery) {
		$this->db = $db;
		$this->amountQuery = $amountQuery;
	}

	/**
	 * @param array $criteria
	 * @return RunnableSelect
	 */
	public function find(array $criteria = []) {
		$req = new RequiredDBFilterMap($criteria);
		$opt = new OptionalDBFilterMap($criteria);
		return $this->db->select()
		->field('sp.id')
		->field('sp.reference')
		->field('p.price')
		->field('spl.name')
		->field('spl.description')
		->from('sp', 'shop#products')
		->joinInner('spl', 'shop#product_descriptions', 'spl.product_id = sp.id')
		->joinLeft('p', $this->amountQuery->getAll($criteria), 'sp.product_id = p.product_id')
		->where('sp.active=?', true)
		->where($req('spl.lang_id=?', 'language.id'))
		->where($opt('sp.reference=?', 'product.reference'))
		->where($opt('spl.name LIKE ?', 'product.name'))
		->where($opt('spl.description LIKE ?', 'product.description'))->debug();
	}

	/**
	 * @param array $data
	 * @return int
	 */
	public function store(array $data) {
		$id = $this->db->insert()
		->setKey('id')
		->into('shop#products')
		->addAll($data, ['id'])
		->addOrUpdateAll($data, ['reference'])
		->run();
		return $id;
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function remove($id) {
		$count = $this->db->delete()
		->from('shop#products')
		->where('id=?', $id)
		->run();
		return $count === 1;
	}
}
```

```PHP
$pr = new ProductRepository($db, new ProductAmountQuery($db));
$pr->find([
	'language' => [
		'id' => 'en',
	],
	'product' => [
		'name' => 'Canon%',
		'description' => '%W-Lan%'
	],
	'pricelist' => [
		'id' => 10,
		'price' => [
			'min' => 35
		],
	],
]);
```

[Back](../README.md)
