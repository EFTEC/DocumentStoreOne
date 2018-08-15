<?php
@ob_start();
ob_implicit_flush(true);
ob_end_flush();

@set_time_limit(60*15); // 15 minutes

use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * It generates 12k invoices
 * @author Jorge Castro Castillo jcastro@eftec.cl
 * @license LGPLv3
 */
echo "loading, please wait 1-5 minutes<br>";
include "../lib/DocumentStoreOne.php";
include "modelinvoices/Models.php";
$delta=0;
try {
    //$flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp',DocumentStoreOne::DSO_MEMCACHE,"localhost:11211");
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'tmp',DocumentStoreOne::DSO_REDIS,"localhost:6379");
    $flatcon->maxLockTime=3;
} catch (Exception $e) {
    die("Unable to create document store ".$e->getMessage());
}
/*
var_dump($flatcon->memcache->add("documentstoreone.","aa",0,3));
var_dump($flatcon->memcache->add("documentstoreone.","aa",0,3));
die(1);
*/

//var_dump($flatcon->insertOrUpdate("benchmark", "fdldflfd fdlfd lfdlfd"));
//var_dump($flatcon->insertOrUpdate("benchmark", "fdldflfd fdlfd lfdlfd"));
/*
var_dump($flatcon->redis->set("documentstoreone.",1,['NX', 'EX' =>1]));
$flatcon->redis->del("documentstoreone.");
//sleep(4);
var_dump($flatcon->redis->set("documentstoreone.",1,['NX', 'EX' => 1]));

die(1);
*/
for($cicle=0;$cicle<10;$cicle++) {
    $t1 = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $flatcon->insertOrUpdate("benchmark", "fdldflfd fdlfd lfdlfd");
    }
    $t2 = microtime(true);
    $delta+=$t2-$t1;
}
echo $delta/10;


// apcu     0.14802496433258 seconds (100 tries)
// folder   0.32476089000702 seconds (100 tries)
// memcache 0.14933934211731 seconds (100 tries)
// redis    2.5403492689133 seconds (100 tries) <-- chihuahua no bueno.