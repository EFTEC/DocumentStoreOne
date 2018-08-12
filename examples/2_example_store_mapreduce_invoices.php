<?php
@ob_start();
ob_implicit_flush(true);
ob_end_flush();
@set_time_limit(60*60); // 1 hour.
use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * It map-reduce invoices according the customers.
 * @author Jorge Castro Castillo jcastro@eftec.cl
 * @license LGPLv3
 */

include "../lib/DocumentStoreOne.php";
include "modelinvoices/Models.php";

@flush();
@ob_flush();


$t1=microtime(true);

try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'invoices');
} catch (Exception $e) {
    die("Unable to create document store");
}


$numItems=0;
$totalInvoice=0;

$listInvoices=$flatcon->select();

$customers=[]; // It's an example to mapreduces. In this case, it reduces the invoice per customers so it generates a customer x invoice table

$igbinary=function_exists('igbinary_serialize');

foreach($listInvoices as $i) {
    if ($i!='genseq_seq') { // we skip the sequence

        if ($igbinary) {
            $inv=igbinary_unserialize($flatcon->get($i));
        } else {
            $invTmp = json_decode($flatcon->get($i)); // $invTmp is stdclass
            $inv = new Invoice();
            DocumentStoreOne::fixCast($inv, $invTmp); // $inv is a Invoice class. However, $inv->details is a stdClass[]
        }

        $customers[$inv->customer->name][] = $i;
    }
}
if ($igbinary) {
    $flatcon->collection("invoicemap")->insertOrUpdate("invoicexcustomer", igbinary_serialize($customers));
} else {
    $flatcon->collection("invoicemap")->insertOrUpdate("invoicexcustomer", json_encode($customers));
}
$t2=microtime(true);
echo "store mapreduce microseconds :".($t2-$t1)." seconds.<br>";


