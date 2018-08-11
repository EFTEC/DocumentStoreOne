# DocumentStoreOne
A flat document store for PHP that allows multiples concurrencies. It is a minimalist alternative to MongoDB without the overhead of installing a new service.

## Key features
- Single key based.
- Fast. However, it's not an alternative to a relational database. It's optimized to store a moderated number documents instead of millions of rows.
- **Allows multiple concurrences by locking and unlocking a document**. If the document is locked then, it retries until the document is unlocked or fails after a number of retries.
- One single class with no dependencies.
- Automatic unlock document locked (by default, every 2 minutes if the file was left locked).

## Usage

```php
<?php
include "lib/DocumentStoreOne.php";
use eftec\DocumentStoreOne\DocumentStoreOne;
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store. Please, check the folder");
}
$flatcon->insertOrUpdate("1",json_encode(array("a1"=>'hello',"a2"=>'world')));
$doc=$flatcon->get("1");
$listKeys=$flatcon->select();
$flatcon->delete("1");
```

## Commands

### Constructor($baseFolder,$schema)

It creates the DocumentStoreOne instance.   **$baseFolder** should be a folder, and **$schema** (a subfolder) is optional.

```php
include "lib/DocumentStoreOne.php";
try {
    $flatcon = new \eftec\DocumentStoreOne\DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store. Please, check the folder");
}
```

### insertOrUpdate($id,$document,[$tries=-1])

inserts a new document (string) in the **$id** indicated. If the document exists then it's updated.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
$doc=json_encode(array("a1"=>'hello',"a2"=>'world')
$flatcon->insertOrUpdate("1",$doc));
```
> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

> It's fast than insert or update.

### insert($id,$document,[$tries=-1])

Inserts a new document (string) in the **$id** indicated. If the document exists then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
$doc=json_encode(array("a1"=>'hello',"a2"=>'world')
$flatcon->insert("1",$doc));
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

### update($id,$document,[$tries=-1])

Update a document (string) in the **$id** indicated. If the document doesn't exist then it returns false  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
$doc=json_encode(array("a1"=>'hello',"a2"=>'world')
$flatcon->update("1",$doc));
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)


### get($id,[$tries=-1])

It reads the document **$id**.  If the document doesn't exist or it's unable to read it, then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
$doc=$flatcon->get("1");
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

### ifExist($id,[$tries=-1])

It checks if the document **$id** exists.  It returns true if the document exists. Otherwise, it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of tries).  
>The validation only happens if the document is fully unlocked.  

```php
$found=$flatcon->ifExist("1");
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

### delete($id,[$tries=-1])

It deletes the document **$id**.  If the document doesn't exist or it's unable to delete then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of tries).  

```php
$doc=$flatcon->delete("1");
```
> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

### list()

It returns all the IDs stored on a schema.  

```php
$listKeys=$flatcon->select();
```
> It includes locked documents.

## Limits
- Keys should be of the type A-a,0-9  
- The limit of document that a schema could hold is based on the document system used. NTFS allows 2 millions of documents per schema.  

## Version list

- 1.0 2018-08-11 first version

## Pending

- Transactional (allows to commit or rollback a multiple step transaction)
