
<?php
echo "<pre>";
$bar = 'BAR';
apcu_add('foo', $bar);
var_dump(apcu_fetch('foo'));
echo "\n";
$bar = 'NEVER GETS SET';
var_dump(apcu_add('foo', $bar));
var_dump(apcu_fetch('foo'));
echo "\n";
echo "</pre>";
?>
