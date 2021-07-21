mysql query builder (php 7.1+)
==============================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rkrx/php-mysql-query-builder/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rkrx/php-mysql-query-builder/?branch=master)
[![Build Status](https://travis-ci.com/rkrx/php-mysql-query-builder.svg)](https://travis-ci.com/rkrx/php-mysql-query-builder)
[![Latest Stable Version](https://poser.pugx.org/rkr/php-mysql-query-builder/v/stable)](https://packagist.org/packages/rkr/php-mysql-query-builder)
[![License](https://poser.pugx.org/rkr/php-mysql-query-builder/license)](https://packagist.org/packages/rkr/php-mysql-query-builder)

Simple mysql query builder to build select, insert, update and delete queries with conditional parts.
This library was initially not intended to build prepared statements, but this is also possible.
The main motive for this library is an environment where a lot of things are automated.

Here a few things to keep in mind:

* The charset is up to you. No special binding to UTF8, although UTF8 is the default.
* You're allowed to nest most queries to build big and powerful queries.
* The order of method-calls of each statement-builder is irrelevant. The resulting query will always render the right order.

## Some simplified examples

* [Initialization](doc/initialization.md)
* [Select](doc/select.md)
* [Insert](doc/insert.md)
* [Update](doc/update.md)
* [Delete](doc/delete.md)
* [Nested transactions](doc/nested-transactions.md)
* [Virtual/alias tables](doc/virtual-tables.md)

## Some extended examples

* [How a simple repository could look like](doc/simple-repository.md)
