<?php
@ob_start();
ob_implicit_flush(true);
ob_end_flush();

@set_time_limit(60*60); // 1 hour.

use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * It generates 12k invoices
 * @author Jorge Castro Castillo jcastro@eftec.cl
 * @license LGPLv3
 */
echo "loading, please wait 1-5 minutes<br>";

@flush();
@ob_flush();



include "../lib/DocumentStoreOne.php";
include "modelinvoices/Models.php";
$t1=microtime(true);
try {
    $flatcon = new DocumentStoreOne(dirname(__FILE__) . "/base", 'invoices');
} catch (Exception $e) {
    die("Unable to create document store");
}
// random names
// 100 invoices x 12 months x 10 years.
// For a smb, it's around 10 years of invoices (most smb generates less than 100 invoices per month)
// https://blog.hubspot.com/marketing/smb-invoicing-infographic
$TOTALINVOICES=100*12*10;
$TOTALINVOICES10=floor($TOTALINVOICES/10);
$numInv=$flatcon->getNextSequence("seq",-1,1,1,$TOTALINVOICES); // it reserves a big chunk at the same time.

$igbinary=function_exists('igbinary_serialize');

for($i=1;$i<=$TOTALINVOICES;$i++) {

    if ($i % $TOTALINVOICES10 ==0) {
        echo "-";
        @flush();
        @ob_flush();
    }

    //$numInv=$flatcon->getNextSequence(); // it slows down the load. For this exercise, it's better to reserve a number of sequences.
    $inv=new Invoice($numInv);
    $idCustomer=rand(0,count($names)-1);
    $inv->customer=new Customer($names[$idCustomer],"Fake Street #".$idCustomer,"555-2444".$idCustomer);
    $numDet=rand(1,20);
    for($e=0;$e<$numDet;$e++) {
        $numProd=rand(0,count($drinks)-1);
        $product=new Product($numProd,$drinks[$numProd]);
        $det=new InvoiceDetail($product,rand(10,200)/10,rand(1,10));
        $inv->details[]=$det;
    }
    if ($igbinary) {
        $doc=igbinary_serialize($inv);
    } else {
        $doc=json_encode($inv);
    }
    $flatcon->insertOrUpdate($numInv,$doc);
    $numInv++;
}

$t2=microtime(true);
echo "<br>Generated in ".($t2-$t1)." seconds<br>";