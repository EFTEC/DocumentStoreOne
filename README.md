# DocumentStoreOne
A document store for PHP that allows multiples concurrencies. It is a minimalist alternative to MongoDB or CouchDB without the overhead of installing a new service.

[![License](https://img.shields.io/badge/license-LGPLV3-blue.svg)]()
[![Maintenance](https://img.shields.io/maintenance/yes/2018.svg)]()
[![php](https://img.shields.io/badge/php->5.4-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![Doc](https://img.shields.io/badge/docs-100%25-green.svg)]()

## Key features
- Single key based.
- Fast. However, it's not an alternative to a relational database. It's optimized to store a moderated number documents instead of millions of rows.
- **Allows multiple concurrences by locking and unlocking a document**. If the document is locked then, it retries until the document is unlocked or fails after a number of retries.
- One single class with no dependencies.
- Automatic unlock document locked (by default, every 2 minutes if the file was left locked).
- It could use **MapReduce** See [example](https://github.com/EFTEC/DocumentStoreOne/blob/master/examples/4_example_read_mapreduce.php)

## Test 

In average, an SMB generates 100 invoices per month. So, let's say that an SMB generates 12000 invoices per decade.  

Testing generating 12000 invoices with customer, details (around 1-5 lines per detail) and date on an i7/ssd/16gb/windows 64bits.

* Store 12000 invoices 45.303 seconds (reserving a sequence range)  
* Store 12000 invoices  73.203 seconds (reading a sequence for every new invoice)
* Store 12000 invoices 49.0286 seconds (reserving a sequence range and using igbinary)   
* Reading all invoices 60.2332 seconds. (only reading) 
* MapReduce all invoices per customers 64.0569 seconds.  
* MapReduce all invoices per customers 32.9869 seconds (igbinary)
* Reading all invoices from a customer **0.3 seconds.** (including render the result, see image)
* Adding a new invoice without recalculating all the MapReduce 0.011 seconds.
  
![mapreduce example](https://github.com/EFTEC/DocumentStoreOne/blob/master/doc/mapreduce.jpg "mapreduce on php")

## Concurrency test

A test with 100 concurrent test (write and read), 10 times.

|N°|	Reads|	(ms)|	Reads|	Error|
|---|---|---|---|---|
|1|100|7471|100|0|
|2|100|7751|100|0|
|3|100|7490|100|0|
|4|100|7480|100|0|
|5|100|8199|100|0|
|6|100|7451|100|0|
|7|100|7476|100|0|
|8|100|7244|100|0|
|9|100|7573|100|0|
|10|100|7818|100|0|

## Usage

```php
include "lib/DocumentStoreOne.php";
use eftec\DocumentStoreOne\DocumentStoreOne;
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store. Please, check the folder");
}
$flatcon->insertOrUpdate("somekey1",json_encode(array("a1"=>'hello',"a2"=>'world'))); // or you could use serialize/igbinary_serialize
$doc=$flatcon->get("somekey1");
$listKeys=$flatcon->select();
$flatcon->delete("somekey1");
```

## Commands

### Constructor($baseFolder,$collection)

It creates the DocumentStoreOne instance.   **$baseFolder** should be a folder, and **$collection** (a subfolder) is optional.

```php
include "lib/DocumentStoreOne.php";
try {
    $flatcon = new \eftec\DocumentStoreOne\DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store. Please, check the folder");
}
```

### isCollection($collection)

Returns true if collection is valid (a subfolder).
```php
$ok=$flatcon->isCollection('tmp');
```
### collection($collection)

It sets the current collection
```php
$flatcon->collection('newcollection'); // it sets a collection.
```
This command could be nested.  

```php
$flatcon->collection('newcollection')->select(); // it sets and return a query
```

> Note, it doesn't validate if the collection is correct.  You must use isCollection to verify if it's right.

### createCollection($collection) 

It creates a collection. It returns false if the operation fails; otherwise it returns true

```php
$flatcon->createCollection('newcollection'); 
```

### insertOrUpdate($id,$document,[$tries=-1])

inserts a new document (string) in the **$id** indicated. If the document exists, then it's updated.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
$doc=json_encode(array("a1"=>'hello',"a2"=>'world')
$flatcon->insertOrUpdate("1",$doc));
```
> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

> It's fast than insert or update.

### insert($id,$document,[$tries=-1])

Inserts a new document (string) in the **$id** indicated. If the document exists, then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
$doc=json_encode(array("a1"=>'hello',"a2"=>'world')
$flatcon->insert("1",$doc));
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

### update($id,$document,[$tries=-1])

Update a document (string) in the **$id** indicated. If the document doesn't exist, then it returns false  
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

### getNextSequence($name="seq",$tries=-1,$init=1,$interval=1,$reserveAdditional=0)

It reads or generates a new sequence.

a) If the sequence exists, then it's incremented by **$interval** and this value is returned.  
b) If the sequence doesn't exist, then it's created with **$init**, and this value is returned.
c) If the library is unable to create a sequence, unable to lock or the sequence exists but, it's unable to read, then it returns false

```php
$seq=$flatcon->getNextSequence();
```

> You could peek a sequence with $id=get('genseq_<name>') however it's not recommended.

> If the sequence is corrupt then it's reset to $init

> If you need to reserve a list of sequences, you could use **$reserveAdditional**

```php
$seq=$flatcon->getNextSequence("seq",-1,1,1,100); // if $seq=1, then it's reserved up to the 101. The next value will be 102.
```

### ifExist($id,[$tries=-1])

It checks if the document **$id** exists.  It returns true if the document exists. Otherwise, it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of tries).  
>The validation only happens if the document is fully unlocked.  

```php
$found=$flatcon->ifExist("1");
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

### delete($id,[$tries=-1])

It deletes the document **$id**.  If the document doesn't exist or it's unable to delete, then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of tries).  

```php
$doc=$flatcon->delete("1");
```
> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 300 that it's around 30 seconds)

### select($mask="*")

It returns all the IDs stored on a collection.  

```php
$listKeys=$flatcon->select();
$listKeys=$flatcon->select("invoice_*");
```
> It includes locked documents.

### fixCast (util class)

It converts a stdclass to a specific class. 

```php
$inv=new Invoice();
DocumentStoreOne::fixCast($inv,$invTmp); //$invTmp is a stdClass();
```

> It doesn't work with members that are array of objects.  The array is kept as stdclass.

## Limits
- Keys should be of the type A-a,0-9  
- The limit of documents that a collection could hold is based on the document system used. NTFS allows 2 millions of documents per collection.  

## Version list

- 1.1 2018-08-12 Changed schema with collection.
- 1.0 2018-08-11 first version

## Pending

- Transactional (allows to commit or rollback a multiple step transaction). It's in evaluation.
- Different strategy of lock (folder,memcache,redis and apcu)