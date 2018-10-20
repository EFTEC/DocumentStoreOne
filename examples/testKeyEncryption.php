<?php

use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
echo "test<br>";

var_dump(hash('sha256',1));

die(1);


try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", '');
    $flatcon->keyEncryption='sha256';

} catch (Exception $e) {
    die("Unable to create document store ".$e->getMessage());
}



echo "<br>setCollection:"; $flatcon->collection('tmpkeyenc',true);
echo "<hr>";
echo "<br>insertOrUpdate:"; var_dump($flatcon->insertOrUpdate("example_1",json_encode(array("a1"=>'hello',"a2"=>'world'))));
echo "<br>insert:"; var_dump($flatcon->insert("example_2",json_encode(array("a1"=>'hello',"a2"=>'world'))));
echo "<br>update:"; var_dump($flatcon->update("example_2",json_encode(array("a1"=>'hola',"a2"=>'mundo'))));
echo "<hr>";
echo "<br>get:"; var_dump($flatcon->get("example_1"));
echo "<hr>";
echo "<br>select:";var_dump($flatcon->select("example_*"));
echo "<hr>";
//$flatcon->delete("1");
echo "<br>delete:"; var_dump($flatcon->delete("example_2"));
echo "<br>sequence "; var_dump($flatcon->collection('tmp2')->getNextSequence());
echo "<br>sequence reserve "; var_dump($flatcon->collection('tmp2')->getNextSequence("seq",-1,1,1,100));
echo "<br>sequence after reserve "; var_dump($flatcon->collection('tmp2')->getNextSequence());
echo "<br>add log "; var_dump($flatcon->collection('tmp')->appendValue('log',"Adding a log\t".date('c')."\n"));