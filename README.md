# DocumentStoreOne
A document store for PHP that allows multiples concurrencies. It is a minimalist alternative to MongoDB or CouchDB without the overhead of installing a new service.  

It also works a a small footprint database.

[![Build Status](https://travis-ci.org/EFTEC/DocumentStoreOne.svg?branch=master)](https://travis-ci.org/EFTEC/DocumentStoreOne)
[![Packagist](https://img.shields.io/packagist/v/eftec/documentstoreone.svg)](https://packagist.org/packages/eftec/documentstoreone)
[![Total Downloads](https://poser.pugx.org/eftec/documentstoreone/downloads)](https://packagist.org/packages/eftec/documentstoreone)
[![License](https://img.shields.io/badge/license-LGPLV3-blue.svg)]()
[![Maintenance](https://img.shields.io/maintenance/yes/2021.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.8-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.4-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![php](https://img.shields.io/badge/php-8.x-green.svg)]()
[![Doc](https://img.shields.io/badge/docs-60%25-green.svg)]()

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
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store. Please, check the folder");
}
$flatcon->insertOrUpdate("somekey1",json_encode(array("a1"=>'hello',"a2"=>'world'))); // or you could use serialize/igbinary_serialize
$doc=$flatcon->get("somekey1");
$listKeys=$flatcon->select();
$flatcon->delete("somekey1");
```

```php
include "lib/DocumentStoreOne.php";
use eftec\DocumentStoreOne\DocumentStoreOne;
$doc=new DocumentStoreOne(__DIR__."/base","task",'folder');
$doc->serializeStrategy='php'; // it sets the strategy of serialization to php
$doc->autoSerialize(true); // autoserialize

$flatcon->insertOrUpdate("somekey1",array("a1"=>'hello',"a2"=>'world')); 
```

## Methods

### Constructor($baseFolder,$collection,$strategy=DocumentStoreOne::DSO_AUTO,$server="",$serializeStrategy = false,$keyEncryption = '')

It creates the DocumentStoreOne instance. 

* **$baseFolder**: should be a folder
* **$collection**: (a subfolder) is optional.
* **$strategy**: It is the strategy used to determine if the file is in use or not.

|strategy|type|server|benchmark|
|---|---|---|---|
|DSO_AUTO|It sets the best available strategy (default)|depends|-|
|DSO_FOLDER|It uses a folder for lock/unlock a document|-|0.3247|
|DSO_APCU|It uses APCU for lock/unlock a document|-|0.1480|
|DSO_REDIS|It uses REDIS for lock/unlock a document|localhost:6379|2.5403 (worst)|
|DSO_NONE|It uses nothing to lock/unlock a document. It is the fastest method but it is unsafe for multiples users||0|

* **$server**: It is used by REDIS. You can set the server used by the strategy.
* **$serializeStrategy**: If false then it does not serialize the information. 

| strategy                 | type                                                                                                                             |
|--------------------------|----------------------------------------------------------------------------------------------------------------------------------|
| php                      | it serializes using serialize() function                                                                                         |
| php_array                | it serializes using include()/var_export()function. The result could be cached on OpCache because the result is a PHP code file. |
| json_object              | it is serialized using json (as object)                                                                                          |
| json_array               | it is serialized using json (as array)                                                                                           |
| csv                      | it serializes using a csv file.                                                                                                  |
| igbinary                 | it serializes using a igbinary file.                                                                                             |
| **none** (default value) | it is not serialized. Information must be serialized/de-serialized manually                                                      |

Examples:

```php
$flatcon = new DocumentStoreOne(__DIR__ . "/base"); // new instance, using the folder /base, without serialization and with the default data

$flatcon = new DocumentStoreOne(__DIR__ . "/base", '','auto','','php_array'); // new instance and serializing using php_array
```

Benchmark how much time (in seconds) it takes to add 100 inserts.   

```php
use eftec\DocumentStoreOne\DocumentStoreOne;
include "lib/DocumentStoreOne.php";
try {
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store.".$e->getMessage());
}
```

```php
use eftec\DocumentStoreOne\DocumentStoreOne;
include "lib/DocumentStoreOne.php";
try {
    $flatcon = new DocumentStoreOne("/base", 'tmp',DocumentStoreOne::DSO_APCU);
} catch (Exception $e) {
    die("Unable to create document store.".$e->getMessage());
}
```

### isCollection($collection)

Returns true if collection is valid (a sub-folder).
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

> Note, it doesn't validate if the collection is correct or exists.  You must use **isCollection()** to verify if it's right.

### autoSerialize($value=true,$strategy='php') 
It sets if we want to auto serialize the information and we set how it is serialized. You can also set using the constructor.

|strategy|type|
|--|---|
|php | it serializes using serialize() function|
|php_array | it serializes using include()/var_export()function. The result could be cached on OpCache because the result is a php file|
|json_object | it is serialized using json (as object)|
|json_array | it is serialized using json (as array)|
|csv | it serializes using a csv file. |
|igbinary | it serializes using a igbinary file. |
|**none** (default value) | it is not serialized. Information must be serialized/de-serialized manually|



### createCollection($collection) 

It creates a collection (a new folder inside the base folder). It returns false if the operation fails; otherwise it returns true

```php
$flatcon->createCollection('newcollection'); 
$flatcon->createCollection('/folder1/folder2'); 
```

### insertOrUpdate($id,$document,[$tries=-1])

inserts a new document (string) in the **$id** indicated. If the document exists, then it's updated.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
// if we are not using auto serialization
$doc=json_encode(["a1"=>'hello',"a2"=>'world']);
$flatcon->insertOrUpdate("1",$doc); // it will create a document called 1.dson in the base folder.

// if we are using auto serialization
$flatcon->insertOrUpdate("1",["a1"=>'hello',"a2"=>'world']);
```
> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 100 tries that equivalent to 10 seconds)

> It's fast than insert or update.

### insert($id,$document,[$tries=-1])

Inserts a new document (string) in the **$id** indicated. If the document exists, then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
// if we are not using auto serialization
$doc=json_encode(array("a1"=>'hello',"a2"=>'world'));
$flatcon->insert("1",$doc);

// if we are using auto serialization
$flatcon->insert("1",["a1"=>'hello',"a2"=>'world']);
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 100 tries that equivalent to 10 seconds)

### update($id,$document,[$tries=-1])

Update a document (string) in the **$id** indicated. If the document doesn't exist, then it returns false  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
// if we are not using auto serialization
$doc=json_encode(["a1"=>'hello',"a2"=>'world']);
$flatcon->update("1",$doc);
// if we are using auto serialization
$flatcon->update("1",["a1"=>'hello',"a2"=>'world']);
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 100 tries that equivales to 10 seconds)


### get($id,[$tries=-1],$default=false)

It reads the document **$id**.  If the document doesn't exist or it's unable to read it, then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
$doc=$flatcon->get("1"); // the default value is false

$doc=$flatcon->get("1",-1,'empty');
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 100 tries that equivalent to 10 seconds)


### getFiltered($id,[$tries=-1],$default=false,$condition=[],$reindex=true)

It reads the document **$id** filtered.  If the document doesn't exist or it's unable to read it, then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of attempts).  

```php
// data in rows [['id'=>1,'cat'=>'vip'],['id'=>2,'cat'=>'vip'],['id'=>3,'cat'=>'normal']];
$data=$this->getFiltered('rows',-1,false,['cat'=>'normal']); // [['id'=>3,'cat'=>'normal']]
$data=$this->getFiltered('rows',-1,false,['type'=>'busy'],false); // [2=>['id'=>3,'cat'=>'normal']]
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 100 tries that equivalent to 10 seconds)

### public function appendValue($name,$addValue,$tries=-1)

It adds a value to a document with name **$name**. The new value is added, so it avoids to create the whole document. It is useful, for example, for a log file.

a) If the value doesn't exist, then it's created with $addValue. Otherwise, it will return true  
b) If the value exists, then **$addValue** is added, and it'll return true  
c) Otherwise, it will return false  

```php
$seq=$flatcon->appendValue("log",date('c')." new log");
```

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
###  getSequencePHP()

It returns an unique sequence (64bit integer) based on time, a random value and a serverId.

> The chances of collision (a generation of the same value) is 1/4095 (per two operations executed every 0.0001 second).

```php
$this->nodeId=1; // if it is not set then it uses a random value each time.
$unique=$flatcon->getSequencePHP(); 
```


### ifExist($id,[$tries=-1])

It checks if the document **$id** exists.  It returns true if the document exists. Otherwise, it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of tries).  
>The validation only happens if the document is fully unlocked.  

```php
$found=$flatcon->ifExist("1");
```

> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 100 tries that equivales to 10 seconds)

### delete($id,[$tries=-1])

It deletes the document **$id**.  If the document doesn't exist or it's unable to delete, then it returns false.  
**$tries** indicates the number of tries. The default value is -1 (default number of tries).  

```php
$doc=$flatcon->delete("1");
```
> If the document is locked then it retries until it is available or after an "nth" number of tries (by default it's 100 tries that equivales to 10 seconds)

### select($mask="*")

It returns all the IDs stored on a collection.  

```php
$listKeys=$flatcon->select();
$listKeys=$flatcon->select("invoice_*");
```
> It includes locked documents.

### copy($idorigin,$iddestination,[$tries=-1])

Copy the document **$idorigin** in **$iddestination** 

```php
$bool=$flatcon->copy(20,30);
```

> If the document destination exists then its replaced

### rename($idorigin,$iddestination,[$tries=-1])

Rename the document **$idorigin** as **$iddestination** 

```php
$bool=$flatcon->rename(20,30);
```


> If the document destination exists then the operation fails.

### fixCast (util class)

It converts a stdclass to a specific class. 

```php
$inv=new Invoice();
$invTmp=$doc->get('someid'); //$invTmp is a stdClass();
DocumentStoreOne::fixCast($inv,$invTmp); 
```

> It doesn't work with members that are array of objects.  The array is kept as stdclass.

## DocumentStoreOne Fields

The next fields are public and they could be changed during runtime

|field|Type|
|---|---|
|$database|string root folder of the database|
|$collection|string Current collection (subfolder) of the database|
|$maxLockTime=120|int Maximium duration of the lock (in seconds). By default it's 2 minutes |
|$defaultNumRetry=100|int Default number of retries. By default it tries 100x0.1sec=10 seconds |
|$intervalBetweenRetry=100000|int Interval (in microseconds) between retries. 100000 means 0.1 seconds |
|$docExt=".dson"|string Default extension (with dot) of the document |
|$keyEncryption=""|string Indicates if the key is encrypted or not when it's stored (the file name). Empty means, no encryption. You could use md5,sha1,sha256,.. |

Example: 
```php
$ds=new DocumentStoreOne();
$ds->maxLockTime=300;
```

```php
$ds=new DocumentStoreOne();
$ds->insert('1','hello'); // it stores the document 1.dson
$ds->keyEncryption='SHA256';
$ds->insert('1','hello'); // it stores the document 6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b.dson
```


## MapReduce

It could be done manually. The system allows to store a pre-calculated value that could be easily accesses (instead of read all values).

Let's say the next exercise, we have a list of purchases

|id|customer|age|sex|productpurchase|amount|
|---|---|---|---|---|---|
|14|john|33|m|33|3|
|25|anna|22|f|32|1|

|productcode|unitprice|
|---|---|
|32|23.3|
|33|30|


John purchased 3 products with the code 33.  The products 33 costs $23.3 per unit.

Question, how much every customer paid?.

> It's a simple exercise, it's more suitable for a relational database (select * from purchases inner join products).
> However, if the document is long or complex to store in the database then it's here where a document store shines.

```php
// 1) open the store
$ds=new DocumentStoreOne('base','purchases'); // we open the document store and selected the collection purchase.
// 2) reading all products
// if the list of products holds in memory then, we could store the whole list in a single document (listproducts key)
$products=$ds->collection('products')->get('listproducts');
// 3) we read the keys of every purchases. It could be slow and it should be a limited set (<100k rows)    
$purchases=$ds->collection('purchases')->select(); // they are keys such as 14,15...

$customerXPurchase=[];
// 4) We read every purchase. It is also slow.  Then we merge the result and obtained the final result
foreach($purchases as $k) {
    $purchase=unserialize( $ds->get($k));
    @$customerXPurchase[$purchase->customer]+=($purchase->amount * @$products[$purchase->productpurchase]); // we add the amount
}
// 5) Finally, we store the result.
$ds->collection('total')->insertOrUpdate(serialize($customerXPurchase),'customerXPurchase'); // we store the result.```
```

|customer|value|
|---|---|
|john|69.9|
|anna|30|

Since it's done on code then it's possible to create an hybrid system (relational database+store+memory cache)

## Limits
- Keys should be of the type A-a,0-9. In windows, keys are not case sensitive. 
- The limit of documents that a collection could hold is based on the document system used. NTFS allows 2 million
 of documents per collection.  



# Strategy of Serialization

Let's say we want to serialize the next information:

```php
$input=[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']];
```

## NONE

The values are not serialized, so it is not possible to serialize an object, array or other structure. It only works with strings.

How values are stored

```
helloworld
```

How values are returned

```php
"helloworld"
```



## PHP

The serialization of PHP is one of the faster way to serialize and de-serialize and it always returns the same value with the same structure (classes, array, fields)

However, the value stored could be long.

How the values are stored:

```
a:2:{i:0;a:2:{s:2:"a1";i:1;s:2:"a2";s:1:"a";}i:1;a:2:{s:2:"a1";i:2;s:2:"a2";s:1:"b";}}
```

How the values are returned:

```
[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']]
```

## PHP_ARRAY

This serialization generates a PHP code. This code is verbose however, it has some nice features:

* It could be cached by PHP's OPcache.
* It's fast to load.

How the values are stored:

```php
<?php /** @generated */
return array (
  0 => 
  array (
    'a1' => 1,
    'a2' => 'a',
  ),
  1 => 
  array (
    'a1' => 2,
    'a2' => 'b',
  ),
);
```

How the values are returned:

```
[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']]
```

## JSON_ARRAY and JSON_OBJECT

Both methods works with JSON for the serialization and de-serialization but the first on returns always a associative array while the other could returns an object (stdClass)

Pro: 

* JSON is fast (but not as fast a PHP's serialization)
* JSON is compatible across different platforms.
* JSON uses fewer space than PHP?s serialization.

Cons:

* It is a big slower than PHP's serialization
* The result could vary and it could returns a different structure (objects are always returned as stdClass)

How the values are stored:

```
[{"a1":1,"a2":"a"},{"a1":2,"a2":"b"}]
```

How the values are returned:

```php
[['a1'=>1,'a2'=>'a'],['a1'=>2,'a2'=>'b']] // array
[stdClass{'a1'=>1,'a2'=>'a'},stdClass{'a1'=>2,'a2'=>'b'}] // object
```



# Control of Error

By default, this library throws errors when an error or exception happens. Some methods allow to avoid to throw errors but most of them could throw an error.

The errors are try/catch catch-ables.

```php
// throw an error:
$this->throwable=true; // (default value is true) If false, then the errors are stored in $this->latestError
try {
    $this->insert('id1','values'); 
} catch($ex) {
    var_dump($ex);
}
```

```php
// not throw an error:
$this->throwable=false; 
$this->insert('id1','values'); 
if($this->latestError) {
	var_dump($this->latestError);
}
$this->resetError();

```



# Working with CSV

You can work with CSV as follows:

```php
$doc=new DocumentStoreOne(__DIR__ . "/base",'','none','','csv'); // set your strategy to csv.
$doc->docExt='.csv'; // (optional), you can set the extension of the document
$doc->csvPrefixColumn='col_'; // (optional), you can set the name of the columns (if the csv doesn't have columns)
$doc->csvStyle(); // (optional) not needing, but you can use to set your own specifications of csv, for example tab-separated, etc.
$doc->regionalStyle(); // (optional) not needing, but you can use to set your own regional settings.
$values=[
    ['name'=>'john1','age'=>22],
    ['name'=>'john2','age'=>22],
    ['name'=>'john3','age'=>22],
    ];
$doc->delete('csv1');
$doc->insert('csv1',$values);
```



# Version list
- 1.20 2021-12-11
  - add igbinary
- 1.19 2021-12-08
  * [added] more controls over the errors.
- 1.18 2021-12-08
  * [added] csv as serialization strategy
  * Some optimizations
  * Memcache is removed.
- 1.16.2 2020-09-20
    * getTimeStamp() throws an exception when the file doesn't exist. Now it returns false.   
- 1.16 2020-09-20
    * new method getTimeStamp()   
- 1.15 2020-09-13
    * method get() now unlocks a document correctly (using method php_array)     
    * method appendValue() is more efficient with json_object,json_array and it works with php_array.   
    * method appendValue() now generates an array of values.
- 1.14 2020-09-13
    * Fixed composer.json. However, the previous composer.json poisoned installations, so it removed all the previous
     version from packagist. 
    * Maybe you should delete composer.lock and the folder vendor\efted\documentstoreone and runs composer update.     
>   [RuntimeException]
>   Could not load package eftec/documentstoreone in repo.packagist.org: [UnexpectedValueException] Could not parse version constraint ^5.6.*: Invalid version string "^5.6.*"          


- 1.13 2020-07-12   
    * method appendValue() now serializes information and works with most method but php_array.    
- 1.12 2020-04-18
    * method get() has a default value
    * method unlock() removed the argument $forced
    * new method getFiltered()
- 1.11 2019-10-23
    * new method setObjectIndex() It sets the default index field for insertObject() and insertOrUpdateObject()
    * new method insertObject() 
    * new method insertOrUpdateObject()
    * method select() now could return a list of indexes of a list of documents
- 1.10 2019-08-30 Some cleaning. Added getSequencePHP() and field nodeId
- 1.9 2019-02-10 Unlock now tries to unlock. Manuallock field is not used anymore.
- 1.8 2018-02-03 field neverLock (for fast access a read only database) also phpunit
- 1.7.3 2018-02-03 Updated composer.json 
- 1.7.1 2018-10-20 Removed an incorrect echo on lock()
- 1.7 2018-10-20 Added key encryption (optional)
- 1.6 2018-10-19 
- - Reduced the default time from 30 seconds to 10 seconds because usually PHP is configured to a timeout of 30 seconds.
- - Method ifExist locks the resource and never releases. Now it releases as expected.
- 1.5 2018-10-13 Maintenance update. Fixed the automatic strategy
- 1.4 2018-08-26 function rename
- 1.3 2018-08-15 Added strategy of lock.
- 1.2 2018-08-12 Small fixes.
- 1.1 2018-08-12 Changed schema with collection.
- 1.0 2018-08-11 first version

## Pending

- Transactional (allows to commit or rollback a multiple step transaction). It's in evaluation.
- ~~Different strategy of lock (folder,redis and apcu)~~
- Msgpack and ~~igbinary~~

