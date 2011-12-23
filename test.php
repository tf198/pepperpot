<?php
$ts = microtime(true);

require_once "classes/sergeant.php";
Sergeant::register();

$grunt = new Grunt();

$grunt->state('Package')->latest('openssh-server');

printf("Executed in %dms using %dKb\n", (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);
