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

## Commands

### Constructor($baseFolder,$schema)

It creates the DocumentStoreOne instance.   The $baseFolder should be a folder and $schema (a subfolder) is optional.

### add($id,$document,[$tries=-1])

Adds a new document (string) in the $id indicated. If the $id exists then it's updated.
**$tries** indicates the number of tries. The default value is -1 (default number of tries).

> If the file is locked then it tries until it is available or after a "nth" number of tries (by default its 20)

### read($id,[$tries=-1])

It reads a document with the $id.  If the file doesn't exists or it's unable to read it then it returns false.
**$tries** indicates the number of tries. The default value is -1 (default number of tries).

> If the file is locked then it tries until it is available or after a "nth" number of tries (by default its 20)

### delete($id,[$tries=-1])

It deletes a document with the $id.  If the file doesn't exists or it's unable to delete then it returns false.
**$tries** indicates the number of tries. The default value is -1 (default number of tries).

> If the file is locked then it tries until it is available or after a "nth" number of tries (by default its 20)

## Limits
- Keys should be of the type A-a,0-9
- The limit of document that a schema could hold is based on the file system used.

## Version list

- 1.0 2018-08-11 first version

## Pending

- Transactional (allows to commit or rollback a multiple step transaction)
