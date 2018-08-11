# DocumentStoreOne
A flat document store for PHP that allows multiples concurrencies. It is a minimalist alternative to MongoDB without the overhead of installing a new service.

## Key features
- Single key based.
- Fast. However, it's not an alternative to a relational database. It's optimized for store a moderated number documents instead of millions of rows.
- Allows multiple concurrences by locking and unlocking a document. If the document is locked then, it retries until the document is unlocked or fails after a number of retries.
- One single class with no dependencies.
- Automatic unlock document locked (by default, every 2 minutes if the file was left locked).

## Usage

```php
<?php
include "lib/DocumentStoreOne.php";
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store. Please, check the folder");
}
$flatcon->add("1",json_encode(array("a1"=>'hello',"a2"=>'world')));
$doc=$flatcon->read("1");
$listKeys=$flatcon->list();
$flatcon->delete("1");
```

## Limits
- Keys should be of the type A-a,0-9
- The limit of document that a schema could hold is based on the file system used.

## Version list

- 1.0 2018-08-11 first version

## Pending

- Transactional (allows to commit or rollback a multiple step transaction)
