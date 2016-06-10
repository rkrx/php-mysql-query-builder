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

[Back](../README.md)
