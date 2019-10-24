<?php

include "../lib/DocumentStoreOne.php";
use eftec\DocumentStoreOne\DocumentStoreOne;
try {
    $flatcon = new DocumentStoreOne("base", 'example','auto',"",true); // it requires the folders base/example
    $flatcon->setObjectIndex('index'); // it is used by insertObject()/InsertOrUpdateObject()
} catch (Exception $e) {
    die("Unable to create document store. Please, check the folder");
}

$countries=['Usa','Canada','Mexico','Australia'];

echo "<h1>Storing</h1>";

$flatcon->insertOrUpdate('list',$countries);

echo "<h1>Reading</h1>";

$read=$flatcon->get('list');
echo "<pre>";
var_dump($read);
echo "</pre>";

echo "<h1>More Storing</h1>";

$item1=['index'=>1,'fruit'=>'apple'];
$item2=['index'=>2,'fruit'=>'apple'];
$item3=['index'=>3,'fruit'=>'apple'];

$flatcon->insertOrUpdateObject($item1,'sequencephp'); // Its the same than $flatcon->insertOrUpdate("1",$item1);
$flatcon->insertOrUpdateObject($item2,'sequencephp'); // $flatcon->insertOrUpdate("2",$item2);
$flatcon->insertOrUpdateObject($item3,'sequencephp'); // $flatcon->insertOrUpdate("3",$item3);


echo "<h1>List Index</h1>";

$listDoc=$flatcon->select('*',false);

echo "<pre>";
var_dump($listDoc);
echo "</pre>";


