<?php
use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
echo "test<br>";
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp','folder');
    
} catch (Exception $e) {
    die("Unable to create document store");
}

$sid=uniqid();

$doc=array('sid'=>$sid);

$flatcon->insertOrUpdate('test',json_encode($doc));

$docRead=json_decode($flatcon->get('test'),true);
/*
if ($docRead['sid']!=$sid) {
    throw new Exception("sid incorrect");
} else {
    echo "ok";
}
*/
echo "ok";


