<?php
$ts = microtime(true);

require_once "classes/lieutenant.php";
Lieutenant::register();

$grunt = new Grunt();

$grunt->state('Package')->latest('openssh-server');

printf("Executed in %dms using %dKb", (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);