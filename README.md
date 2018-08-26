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

### Constructor($baseFolder,$collection,$strategy=DocumentStoreOne::DSO_AUTO,$server="")

It creates the DocumentStoreOne instance.   **$baseFolder** should be a folder, and **$collection** (a subfolder) is optional.

|strategy|type|server|benchmark|
|---|---|---|---|
|DSO_AUTO|It sets the best available strategy (default)|depends|-|
|DSO_FOLDER|It uses a folder for lock/unlock a document|-|0.3247|
|DSO_APCU|It uses APCU for lock/unlock a document|-|0.1480|
|DSO_MEMCACHE|It uses MEMCACHE for lock/unlock a document|localhost:11211|0.1493|
|DSO_REDIS|It uses REDIS for lock/unlock a document|localhost:6379|2.5403 (worst)|

Benchmark how much time (in seconds) it takes to add 100 inserts.   

```php
use eftec\DocumentStoreOne\DocumentStoreOne;
include "lib/DocumentStoreOne.php";
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp');
} catch (Exception $e) {
    die("Unable to create document store.".$e->getMessage());
}
```

```php
use eftec\DocumentStoreOne\DocumentStoreOne;
include "lib/DocumentStoreOne.php";
try {
    $flatcon = new DocumentStoreOne("/base", 'tmp',DocumentStoreOne::DSO_MEMCACHE,"localhost:11211");
} catch (Exception $e) {
    die("Unable to create document store.".$e->getMessage());
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

### public function appendValue($name,$addValue,$tries=-1)

It adds a value to a document with name $name. For example, for a log file.  

a) If the value doesn't exists, then it's created with $addValue. Otherwise, it will return true  
b) If the value exists, then $addValue is added and it'll return true  
c) Otherwise,, it will return false  

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
DocumentStoreOne::fixCast($inv,$invTmp); //$invTmp is a stdClass();
```

> It doesn't work with members that are array of objects.  The array is kept as stdclass.

## DocumentStoreOne Fields

The next fields are public and they could be changed during runtime

|field|Type|
|---|---|
|$database|string root folder of the database|
|$collection|string Current collection (subfolder) of the database|
|$maxLockTime=120|int Maximium duration of the lock (in seconds). By default it's 2 minutes |
|$defaultNumRetry=300|int Default number of retries. By default it tries 300x0.1sec=30 seconds |
|$intervalBetweenRetry=100000|int Interval (in microseconds) between retries. 100000 means 0.1 seconds |
|$docExt=".dson"|string Default extension (with dot) of the document |

Example: 
```php
$ds=new DocumentStoreOne();
$ds->maxLockTime=300;
```

## MapReduce

It is easy.  If you store an object (or array of objects), then you don't need to map it.

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
> However, if the document is long then it's here where a document store shines.

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
- The limit of documents that a collection could hold is based on the document system used. NTFS allows 2 millions of documents per collection.  

## Version list

- 1.4 2018-08-26 function rename
- 1.3 2018-08-15 Added strategy of lock.
- 1.2 2018-08-12 Small fixes.
- 1.1 2018-08-12 Changed schema with collection.
- 1.0 2018-08-11 first version

## Pending

- Transactional (allows to commit or rollback a multiple step transaction). It's in evaluation.
- Different strategy of lock (folder,memcache,redis and apcu)