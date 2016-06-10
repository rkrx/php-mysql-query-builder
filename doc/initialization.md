# Initialization

```PHP
$pdo = new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
```

```PHP
$mysql = new MySQL($pdo);
$mysql->getAliasRegistry()->add('t', 'testdb.test__');
```

[Back](../README.md)