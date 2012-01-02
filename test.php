<?php
$ts = microtime(true);

require_once "classes/sergeant.php";
Sergeant::register();

//$grunt = new Grunt('192.168.100.102', 'grunt', 'minion');
$grunt = new Grunt('127.0.0.1', null, null);
//$grunt->state('Package')->latest('openssh-server');
//var_dump($grunt->task('test')->verify());
var_dump($grunt->task('pkg')->current('openssh-server'));
var_dump($grunt->task('pkg')->available('openssh-server'));
//var_dump($grunt->probe('os')->get('name'));

printf("Executed in %dms using %dKb\n", (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);
