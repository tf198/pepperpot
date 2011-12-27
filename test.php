<?php
$ts = microtime(true);

require_once "classes/sergeant.php";
Sergeant::register();

$grunt = new Grunt('127.0.0.1', 'grunt', 'minion');
//$grunt->state('Package')->latest('openssh-server');
$grunt->task('test')->verify();

printf("Executed in %dms using %dKb\n", (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);
