<?php

use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
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
//$flatcon->delete("1");