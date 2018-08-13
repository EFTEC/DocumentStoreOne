<?php
$r=new Redis();
$r->connect("127.0.0.1");

$r->set("lock.1","lock",120);

var_dump($r->get("lock.1"));