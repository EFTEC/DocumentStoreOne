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

$igbinary=function_exists('igbinary_serialize');

try {
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", 'invoices');
} catch (Exception $e) {
    die("Unable to create document store");
}
$flatcon->collection("invoicemap");
$customers=$flatcon->get("invoicexcustomer");
if (!$customers) {
    die("you must run example_mapreduce_invoice.php before");
}

if ($igbinary) {
    $customers=igbinary_unserialize($customers);
} else {
    $customers=json_decode($customers,true);
}


$flatcon->collection("invoices");
$numItems=0;
$totalInvoice=0;
$idCustomer='Yasmin Trace';
if (!isset($customers[$idCustomer])) die("no invoices for Yasmin Trace");
$listInvoices=$customers[$idCustomer];


echo '<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">';
echo "<body class='container-fluid'>";
echo "<h1>Invoices of $idCustomer. It uses mapreduces</h1>";
echo "<table class='table table-striped table-bordered'>";
echo "<thead class='thead-dark'><tr><th>Invoice #</th><th>Date</th><th>Detail Num</th></tr></thead>";
foreach($listInvoices as $i) {
    if ($igbinary) {
        $inv=igbinary_unserialize($flatcon->get($i));
    } else {
        $invTmp = json_decode($flatcon->get($i)); // $invTmp is stdclass
        $inv = new Invoice();
        DocumentStoreOne::fixCast($inv, $invTmp); // $inv is a Invoice class. However, $inv->details is a stdClass[]
    }

    echo "<tr><td>{$inv->idInvoice}</td><td>{$inv->date}</td><td><table class='table table-bordered'>";
    echo "<thead class='thead-dark'><tr><th>Product #</th><th>Price</th><th>Amount</th></tr></thead>";
    foreach($inv->details as $d) {
        echo "<tr><td>".$d->product->name."</td><td>".$d->unitPrice."</td><td>".$d->amount."</td></tr>";
    }
    echo "</table></td></tr>";
}
echo "</table>";
$t2=microtime(true);



echo "Mapreduce microseconds :".($t2-$t1)." seconds.<br>";
echo "</body>";

