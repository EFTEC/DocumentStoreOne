<?php
@set_time_limit(60*60); // 1 hour.

use eftec\DocumentStoreOne\DocumentStoreOne;

/**
 * It generates 12k invoices
 * @author Jorge Castro Castillo jcastro@eftec.cl
 * @license LGPLv3
 */


include "../lib/DocumentStoreOne.php";
include "modelinvoices/Models.php";
$t1=microtime(true);

try {
    $flatcon = new DocumentStoreOne(__DIR__ . "/base", 'invoices');
} catch (Exception $e) {
    die("Unable to create document store");
}
// random names
$names=array('Glendora Bowland','Magan Brungardt','Sarah Lamphere','Lavinia Shaughnessy','Glynda Bayless','Han Salaam','Loree Oloughlin','Mercedez Pretty','Rachell Gatts','Isaiah Quinto','Katharyn Cesar','Catrice Lopinto','Albertina Rady','Rafaela Zuniga','Bruna Steadman','Mira Nick','Reita Scheidler','Elvia Almada','Delaine Eastwood','Odis Havlik','Danial Stanford','Jacquelyn Lefebvre','Krystina Moritz','Gerard Petre','Zella Ciesla','Shelley Pitcock','Delinda Frizzell','Edelmira Christina','Scarlet Held','Cassaundra Kurt','Bella Harling','Carli Orrell','Catherine Flanigan','Klara Crompton','Ashely Cordoba','Jim Washam','Gertha Hereford','Tam Grenz','Erinn Bieker','Audrie Harrah','Sook Applewhite','Mayme Rosin','Eloy Hennessey','Nedra Fleener','Marta Ahlgren','Latosha Heim','Mckinley Bookout','Bonita Salisbury','Tabetha Blose','Jacinto Martens','Shelly Ponte','Letitia Abernathy','Blanca Brazan','Mari Maltese','Rhona Lesesne','Mammie Bjornson','Jonathan Warburton','Wynona Westlund','Terisa Hartsock','Delilah Kovacich','Li Issa','Ruth Parrish','Azucena Sprankle','Pamula Copas','Tonia Heatwole','Simonne Mash','Alonzo Mcmakin','Sandra Richey','Jon Skeens','Cherilyn Smalls','Millicent Lenton','Treena Wolken','Jamika Cardinal','Andrew Griffey','Tiana Corner','Linnea Perham','Rea Mullens','Lorita Clune','Trista Newland','Seth Kimler','Eda Gittens','Grace Stoval','Helaine Weidenbach','Irving Fuhr','Laureen Tankersley','Mohammed Castleman','Werner Azcona','Della Pollard','Bernie Lubin','Ara Gruner','Tonette Wurst','Betsey Whitworth','Adrianna Epps','Elvina Mattox','Ilene Bidwell','Louie Gladden','Jenice Desilets','Sharlene Woolverton','Nana Pettit','Yasmin Trace');
$igbinary=function_exists('igbinary_serialize');

$numInv=$flatcon->getNextSequence("seq");
$inv=new Invoice($numInv);
$idCustomer=rand(0,count($names)-1);
$inv->customer=new Customer($names[$idCustomer],"Fake Street #".$idCustomer,"555-2444".$idCustomer);
$numDet=rand(1,3);
for($e=0;$e<$numDet;$e++) {
    $numProd=rand(0,count($drinks)-1);
    $product=new Product($numProd,$drinks[$numProd]);
    $det=new InvoiceDetail($product,rand(0,2000),rand(1,10));
    $inv->details[]=$det;
}

if ($igbinary) {
    $flatcon->collection("invoices")->insertOrUpdate($numInv,igbinary_serialize($inv)); // store a new invoice
} else {
    $flatcon->collection("invoices")->insertOrUpdate($numInv,json_encode($inv)); // store a new invoice
}


$mapReduceCustomers=$flatcon->collection("invoicemap")->get("invoicexcustomer"); // get the map reduce.
if (!$mapReduceCustomers) {
    die("you must run example_mapreduce_invoice.php before");
}
if ($igbinary) {
    $mapReduceCustomers=igbinary_unserialize($mapReduceCustomers);
} else {
    $mapReduceCustomers=json_decode($mapReduceCustomers,true);
}

@$mapReduceCustomers[$inv->customer->name][]=$numInv;
echo "Adding invoice $numInv to customer {$inv->customer->name}<br>";

$flatcon->collection("invoicemap")->insertOrUpdate("invoicexcustomer",json_encode($mapReduceCustomers)); // we store back the map reduce.

$t2=microtime(true);
echo "Generated in ".($t2-$t1)." seconds<br>";