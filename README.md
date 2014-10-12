mysql query builder (php 5.4+)
==============================
Simple mysql query builder to build select, insert, update and delete queries with conditional parts.
This library was initially not intended to build prepared statements, but this is also possible.
The main motive for this library is an environment where a lot of things are automated.

Here a few things to keep in mind:

* The charset is up to you. No special binding to UTF8, although UTF8 is the default.
* The order of method-calls of each statement-builder is irrelevant. The resulting query will always render the right order.
* No animals were harmed due to the production of this library.