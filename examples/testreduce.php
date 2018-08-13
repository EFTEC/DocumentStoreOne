<?

use eftec\DocumentStoreOne\DocumentStoreOne;

include "../lib/DocumentStoreOne.php";
include "modelinvoices/Models.php";

try {
    $ds = new DocumentStoreOne('base', 'purchases');
} catch (Exception $e) {
    die($e->getMessage());
}

$products=$ds->collection('products')->get('listproducts'); // if the list of products is limited then, we could store the whole list in a single document.
$purchases=$ds->collection('purchases')->select(); // they are keys such as 14,15...

$customerXPurchase=[];

foreach($purchases as $k) {
    $purchase=unserialize( $ds->get($k));
    @$customerXPurchase[$purchase->customer]+=($purchase->amount * @$products[$purchase->productpurchase]); // we add the amount
}

$ds->collection('total')->insertOrUpdate(serialize($customerXPurchase),'customerXPurchase'); // we store the result.