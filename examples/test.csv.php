<?php
use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
echo "test csv<br>";

$doc=new DocumentStoreOne(__DIR__ . "/base",'','none','','csv');
$doc->docExt='.csv';
$doc->csvPrefixColumn='col_';
$doc->csvStyle(); // not needing, but you can use to set your own specifications of csv.
$doc->regionalStyle(); // not needing, but you can use to set your own regional settins.

$values=[
    ['name'=>'jo"hn1','age'=>22],
    ['name'=>'john2','age'=>22],
    ['name'=>'john3','age'=>22],
    ];
$doc->delete('csv1');
$doc->insertOrUpdate('csv1',$values);
$values[1]=['name'=>'john2x','age'=>22];
$doc->update('csv1',$values);

$doc->appendValue('csv1',['name'=>'john4','age'=>22]);
$doc->appendValue('csv1',['name'=>'john5','age'=>22]);
$doc->appendValue('csv1',['name'=>'john6','age'=>22]);

//$csv=$doc->get('csv1');
echo "<pre>";
echo "get v1:<br>";

$csv=$doc->get('csv1');
var_dump($csv);

echo "</pre>";

$values=[
    ['john1',22],
    ['john2',23],
    ['john3',22],
];

$doc->delete('csv2');
$doc->csvHeader=false;
$doc->insert('csv2',$values);
echo "<pre>";
var_dump($doc->get('csv2'));
echo "</pre>";
