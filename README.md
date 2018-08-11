# DocumentStoreOne
A flat document store for PHP that allows multiples concurrencies. It is a minimalist alternative to MongoDB without the overhead of installing a new service.

## Key features
- Fast
- Allows multiple concurrences by using locking and unlocking
- One single class with no dependencies.
- Automatic unlock document locked (by default, every 2 minutes if the file was left locked).

## Usage

```php
<?php
include "xdev/DocumentStoreOne.php";
echo "test<br>";
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", '');
} catch (Exception $e) {
    die("Unable to create document store");
}

var_dump($flatcon->setSchema('tmp'));
echo "<hr>add<br>";
var_dump($flatcon->add("1",json_encode(array("a1"=>'hello',"a2"=>'world'))));
echo "<hr>get:<br>";
var_dump($flatcon->read("1"));
echo "<hr>";
var_dump($flatcon->list());
echo "<hr>";
$flatcon->delete("1");
```
