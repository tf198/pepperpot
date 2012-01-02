<?
$ts = microtime(true);

if($argc<4) _fail("Not enough arguments");

$identifier = $argv[1];
$task = $argv[2];
$method = $argv[3];

$config = include("machines.php");

require_once "classes/sergeant.php";
Sergeant::register();

$i = 0;
foreach($config as $name => $info) {
  if(preg_match("/{$identifier}/", $name)) {
    $machine = new Grunt($info);
    echo "{$name}: " . print_r($machine->task($task)->$method(), true) . "\n";
    $i++;
  }
}

printf("Contacted %d hosts in %dms using %dKb\n", $i, (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);

function _fail($message, $code=1) {
  global $argv;
  fputs(STDERR, $message . "\n");
  fputs(STDERR, "usage: php {$argv[0]} <identifier> <task> <method>\n");
  exit($code);
}
?>