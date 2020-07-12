<?php

use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
echo "test<br>";
try {
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", 'tmp',DocumentStoreOne::DSO_FOLDER);
} catch (Exception $e) {
    die("Unable to create document store");
}

$flatcon->insertOrUpdate("original","it is the original document");
var_dump($flatcon->copy("original","copy"));
echo "<br>wrong copy:<br>";
var_dump($flatcon->copy("originalwrong","copy"));