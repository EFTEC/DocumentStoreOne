<?php
@set_time_limit(60*60); // 1 hour.
use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * It map-reduce invoices according the customers.
 * @author Jorge Castro Castillo jcastro@eftec.cl
 * @license LGPLv3
 */

include "../lib/DocumentStoreOne.php";
include "modelinvoices/Models.php";

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

foreach($listInvoices as $i) {
    if ($i!='genseq_seq') { // we skip the sequence
        $invTmp = json_decode($flatcon->get($i)); // $invTmp is stdclass
        $inv = new Invoice();
        DocumentStoreOne::fixCast($inv, $invTmp); // $inv is a Invoice class. However, $inv->details is a stdClass[]

        $customers[$inv->customer->name][] = $i;
    }
}

$flatcon->schema("invoicemap")->insertOrUpdate("invoicexcustomer",json_encode($customers));
$t2=microtime(true);
echo "store mapreduce microseconds :".($t2-$t1)." seconds.<br>";


