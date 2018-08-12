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
$names=array('Glendora Bowland','Magan Brungardt','Sarah Lamphere','Lavinia Shaughnessy','Glynda Bayless','Han Salaam','Loree Oloughlin','Mercedez Pretty','Rachell Gatts','Isaiah Quinto','Katharyn Cesar','Catrice Lopinto','Albertina Rady','Rafaela Zuniga','Bruna Steadman','Mira Nick','Reita Scheidler','Elvia Almada','Delaine Eastwood','Odis Havlik','Danial Stanford','Jacquelyn Lefebvre','Krystina Moritz','Gerard Petre','Zella Ciesla','Shelley Pitcock','Delinda Frizzell','Edelmira Christina','Scarlet Held','Cassaundra Kurt','Bella Harling','Carli Orrell','Catherine Flanigan','Klara Crompton','Ashely Cordoba','Jim Washam','Gertha Hereford','Tam Grenz','Erinn Bieker','Audrie Harrah','Sook Applewhite','Mayme Rosin','Eloy Hennessey','Nedra Fleener','Marta Ahlgren','Latosha Heim','Mckinley Bookout','Bonita Salisbury','Tabetha Blose','Jacinto Martens','Shelly Ponte','Letitia Abernathy','Blanca Brazan','Mari Maltese','Rhona Lesesne','Mammie Bjornson','Jonathan Warburton','Wynona Westlund','Terisa Hartsock','Delilah Kovacich','Li Issa','Ruth Parrish','Azucena Sprankle','Pamula Copas','Tonia Heatwole','Simonne Mash','Alonzo Mcmakin','Sandra Richey','Jon Skeens','Cherilyn Smalls','Millicent Lenton','Treena Wolken','Jamika Cardinal','Andrew Griffey','Tiana Corner','Linnea Perham','Rea Mullens','Lorita Clune','Trista Newland','Seth Kimler','Eda Gittens','Grace Stoval','Helaine Weidenbach','Irving Fuhr','Laureen Tankersley','Mohammed Castleman','Werner Azcona','Della Pollard','Bernie Lubin','Ara Gruner','Tonette Wurst','Betsey Whitworth','Adrianna Epps','Elvina Mattox','Ilene Bidwell','Louie Gladden','Jenice Desilets','Sharlene Woolverton','Nana Pettit','Yasmin Trace');
// 100 invoices x 12 months x 10 years.
// For a smb, it's around 10 years of invoices
// https://blog.hubspot.com/marketing/smb-invoicing-infographic
$TOTALINVOICES=100*12*10;

$numInv=$flatcon->getNextSequence("seq",-1,1,1,$TOTALINVOICES); // it reserves a big chunk at the same time.

$igbinary=function_exists('igbinary_serialize');

for($i=1;$i<=$TOTALINVOICES;$i++) {
    //$numInv=$flatcon->getNextSequence(); // it slows down the load. For this exercise, it's better to reserve a number of sequences.
    $inv=new Invoice($numInv);
    $idCustomer=rand(0,count($names)-1);
    $inv->customer=new Customer($names[$idCustomer],"Fake Street #".$idCustomer,"555-2444".$idCustomer);
    $numDet=rand(1,3);
    for($e=0;$e<$numDet;$e++) {
        $det=new InvoiceDetail($e,rand(0,2000),rand(1,10));
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
echo "Generated in ".($t2-$t1)." seconds<br>";