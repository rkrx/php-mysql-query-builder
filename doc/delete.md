# Delete

You can use joins in delete-statements. But only the rows of tables specified in `from` will be modified (deleted).

```PHP
$mysql->delete()
->from('t1', 'test1')
->joinLeft('t2', 'test2', 't1.id=t2.test_id')
->where('t1.field1=? AND t2.field2 > ?', 1, 10)
->run();
```

[Back](../README.md)
