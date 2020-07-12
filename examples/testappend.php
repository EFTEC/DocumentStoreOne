<?php

use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
echo "<h2>test appendvalue (json_array)</h2>";
try {
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", '');
    $flatcon->autoSerialize(true,'json_array');
    
} catch (Exception $e) {
    die("Unable to create document store");
}
$flatcon->collection('tmp')->delete('newarray');
//$flatcon->collection('tmp')->insert('newarray','');
$flatcon->collection('tmp')->appendValue('newarray',['a1'=>1,'a2'=>2,'n'=>'cocacola']);
$flatcon->collection('tmp')->appendValue('newarray',['a1'=>2,'a2'=>3,'n'=>'fanta']);
$flatcon->collection('tmp')->appendValue('newarray',['a1'=>3,'a2'=>4,'n'=>'sprite']);
$flatcon->collection('tmp')->appendValue('newarray',['a1'=>4,'a2'=>5,'n'=>'seven up']);

$obj=$flatcon->collection('tmp')->get('newarray');

echo"<pre>";
var_dump($obj);
echo "</pre>";


echo "<h2>test appendvalue (php_array is not compatible)</h2>";
try {
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", '');
    $flatcon->autoSerialize(true,'php_array');

} catch (Exception $e) {
    die("Unable to create document store");
}
$flatcon->collection('tmp')->delete('newarray2');
//$flatcon->collection('tmp')->insert('newarray','');
$flatcon->collection('tmp')->appendValue('newarray2',['a1'=>1,'a2'=>2,'n'=>'cocacola']);
$flatcon->collection('tmp')->appendValue('newarray2',['a1'=>2,'a2'=>3,'n'=>'fanta']);
$flatcon->collection('tmp')->appendValue('newarray2',['a1'=>3,'a2'=>4,'n'=>'sprite']);
$flatcon->collection('tmp')->appendValue('newarray2',['a1'=>4,'a2'=>5,'n'=>'seven up']);

$obj=$flatcon->collection('tmp')->get('newarray2');

echo"<pre>";
var_dump($obj);
echo "</pre>";
