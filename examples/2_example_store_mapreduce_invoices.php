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
include __DIR__.'/../vendor/autoload.php';
include "modelinvoices/Models.php";
echo "<h1>Map Reduce</h1>";
echo "We have several invoices, we are mapping an invoice with a customer, so we could consult all the invoices x customer without reading all the invoices<br>";
echo "The limit of this strategy is the size of the file and it must be open on memory<br>";
echo "generating map reduce for invoice per customer...<br>";
@flush();
@ob_flush();


$t1=microtime(true);

try {
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", 'invoices');
    $flatcon->autoSerialize(true,'auto');
} catch (Exception $e) {
    die("Unable to create document store");
}


$listInvoices=$flatcon->select();

$customers=[]; // It's an example to mapreduces. In this case, it reduces the invoice per customers so it generates a customer x invoice table


foreach($listInvoices as $i) {
    if ($i !== 'genseq_seq') { // we skip the sequence
        $inv=$flatcon->get($i);
        $customers[$inv->customer->name][] = $i;
    }
}
$flatcon->collection("invoicemap")->insertOrUpdate("invoicexcustomer", $customers);
$t2=microtime(true);
echo "store mapreduce microseconds :".($t2-$t1)." seconds.<br>";
echo "<hr>";
echo "<pre>";
echo json_encode($customers,JSON_PRETTY_PRINT);
echo "</pre>";


