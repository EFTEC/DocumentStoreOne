<?php

use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
echo "test<br>";
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", '');
} catch (Exception $e) {
    die("Unable to create document store");
}

echo "<br>setSchema:"; var_dump($flatcon->setSchema('tmp'));
echo "<hr>";
echo "<br>insertOrUpdate:"; var_dump($flatcon->insertOrUpdate("1",json_encode(array("a1"=>'hello',"a2"=>'world'))));
echo "<br>insert:"; var_dump($flatcon->insert("2",json_encode(array("a1"=>'hello',"a2"=>'world'))));
echo "<br>update:"; var_dump($flatcon->update("2",json_encode(array("a1"=>'hola',"a2"=>'mundo'))));
echo "<hr>";
echo "<br>get:"; var_dump($flatcon->get("1"));
echo "<hr>";
echo "<br>select:";var_dump($flatcon->select());
echo "<hr>";
//$flatcon->delete("1");
echo "<br>delete:"; var_dump($flatcon->delete("2"));